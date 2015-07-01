<?php
// PHP GitHub Deploy class
class PHP_GitHub_Deploy {
  // Configuration array
  protected $config;
  // Main constructor
  public function __construct ($config = array()) {
    // Array of required configuration keys
    $required = array(
      // Required remote location
      'remote',
      // Required target location
      'target'
    );
    // Passed configuration is not an array
    if (!is_array($config)) {
      // Exit with an error
      $this->error('<strong>Valid configuration array required.</strong>', 500);
    }
    // Loop through each required key
    foreach ($required as $key) {
      // Required key was not passed
      if (!array_key_exists($key, $config)) {
        // Exit with an error
        $this->error('<strong>Configuration key \'' . $key . '\' required.</strong>', 500);
      }
    }
    // Default configuration array
    $default = array(
      // False for no secret
      'secret' => FALSE,
      // Master branch
      'branch' => 'master',
      // Don't print commands for debugging
      'debug' => FALSE,
      // Temporary directory as remote location hash
      'temp' => '/tmp/' . md5($config['remote'])
    );
    // Initialize the configuration array
    $this->config = array_merge($default, $config);
  }
  // Main deployment function
  public function deploy () {
    // If we need to validate via a secret token
    if ($this->config['secret']) {
      // Validate the secret request token
      $this->validate_secret();
    }
    // Run the needed commands
    $this->run();
  }
  // Output an error with a response code and exit
  private function error ($message='', $code) {
    // Echo the message in a paragraph
    echo '<p>' . $message . '</p>';
    // Set the response code
    http_response_code($code);
    // Terminate the script
    exit();
  }
  // Validate the secret request token
  private function validate_secret ($secret) {
    // HTTP_X_HUB_SIGNATURE header not provided
    if (!array_key_exists('HTTP_X_HUB_SIGNATURE', $_SERVER)) {
      // Exit with an error
      $this->error('Valid HTTP_X_HUB_SIGNATURE header required.', 403);
    }
    // Attempt to grab the raw request body
    $body = file_get_contents('php://input');
    // Error while grabbing the raw request body
    if (!$body) {
      // Exit with an error
      $this->error('Could not retrieve the request content.', 403);
    }
    // Compute the expected secret access token signature
    $expected = 'sha1=' . hash_hmac('sha1', $body, $this->config['secret']);
    // If the secret access token signature is not what we expect
    if ($_SERVER['HTTP_X_HUB_SIGNATURE'] != $expected) {
      // Exit with an error
      $this->error('Invalid HTTP_X_HUB_SIGNATURE header provided.', 403);
    }
  }
  // Run the needed commands
  private function run () {
    // Array of commands
    $commands = array();
    // If the temporary directory exists
    if (file_exists($this->config['temp'])) {
      // Remove the temporary directory
      $commands[] = array('Removing the temporary directory. ', sprintf('rm -rf %s',
        // Temporary directory
        $this->config['temp']
      ));
    }
    // Create the temporary directory
    $commands[] = array('Creating the temporary directory.', sprintf('mkdir %s',
      // Temporary directory
      $this->config['temp']
    ));
    // Clone repository files into the temporary directory
    $commands[] = array('Cloning files from the remote repository to the temporary directory.', sprintf('git clone --branch %s %s %s',
      // Repository branch
      $this->config['branch'],
      // Remote directory
      $this->config['remote'],
      // Temporary directory
      $this->config['temp']
    ));
    // Copy temporary directory files into the target directory
    $commands[] = array('Synching the temporary directory to the target directory.', sprintf('rsync -a --delete %s %s %s',
      // Exclude the git folder
      '--exclude=.git',
      // Temporary directory
      $this->config['temp'] . '/',
      // Target directory
      $this->config['target']
    ));
    // Remove the temporary directory
    $commands[] = array('Removing the temporary directory.', sprintf('rm -rf %s',
      // Temporary directory
      $this->config['temp']
    ));
    // Execute each command in the array
    foreach ($commands as $command) {
      // Output the command title we are executing
      echo '<p><strong>' . $command[0] . '</strong></p>';
      // If we are to print the commands for debugging
      if ($this->config['debug']) {
        // Output the command we are executing
        echo '<p>' . $command[1] . '</p>';
      }
      // Execute the command and store the result
      // '2>&1' forces output to stdout so we can read it
      exec($command[1] . ' 2>&1', $output, $error);
      // If there was a command error
      if ($error) {
        // Exit with an error
        $this->error('<strong>Error:</strong> ' . implode(' ', $output), 500);
      }
    }
  }
}
?>
