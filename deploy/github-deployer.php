<?php

// GitHub Deployer class
class GithubDeployer {

  /*
  * Variables
  */

  // Configuration array
  protected $config;

  /*
  * Constructors
  */

  // Main constructor
  // @input $config array
  // ['remote'] string  Required  Repository ssh url.
  // ['target'] string  Required  Deployment directory.
  // ['secret'] string  Optional  Secret access token. Defaults to false.
  // ['branch'] string  Optional  Respository branch. Defaults to 'master'.
  // ['ignore'] string  Optional  Array of files and folders to ignore. Defaults to array('.git').
  // ['debug']  string  Optional  Debug output flag. Defaults to false.
  // ['temp']   array   Optional  Temporary directory to store files. Defaults to /tmp/md5($config['remote'])
  public function __construct ($config) {

    // Validate configuration parameter
    if (!is_array($config)) $this->throw_error('Configuration parameter must be of type array.', 500);

    // Required parameters
    $required = array(
      'remote',
      'target'
    );

    // Validate configuration keys
    foreach ($required as $key) {
      // Missing configuration parameter
      if (!array_key_exists($key, $config)) $this->throw_error('Configuration key \'' . $key . '\' required.', 500);
    }

    // Default configuration
    $default = array(
      'secret' => FALSE,
      'branch' => 'master',
      'ignore' => array('.git'),
      'debug' => FALSE,
      'temp' => '/tmp/' . md5($config['remote'])
    );

    // Initialize overall configuration
    $this->config = array_merge($default, $config);
  }

  /*
  * Public Functions
  */

  // Main deployment function
  public function deploy () {

    // Validate the secret request token
    if (!$this->valid_request()) $this->throw_error('Invalid request authentication.', 403);

    // Array of commands and their output messages
    $commands = array();

    // Remove the temporary directory if it exists
    $commands[] = array(
      'Removing old temporary directory.',
      sprintf('rm -rf %s',
        $this->config['temp']
      )
    );

    // Create the temporary directory
    $commands[] = array(
      'Creating new temporary directory.',
      sprintf('mkdir %s',
        $this->config['temp']
      )
    );

    // Clone repository files into the temporary directory
    $commands[] = array(
      'Cloning repository files into temporary directory.',
      sprintf('git clone --branch %s %s %s',
        $this->config['branch'],
        $this->config['target'],
        $this->config['temp']
      )
    );

    // Sync target directory with cloned repository files
    $commands[] = array(
      'Syncing target directory with cloned repository files.',
      sprintf('rsync -a --delete %s %s %s',
        array_map(function ($f) { return '--exclude=' . $f; }, $this->config['ignore']),
        $this->config['remote'],
        $this->config['temp']
      )
    );

    // Remove the temporary directory
    $commands[] = array(
      'Removing temporary directory.',
      sprintf('rm -rf %s',
        $this->config['temp']
      )
    );

    // Run through each command
    foreach ($commands as $command) {
      // Print command title
      $this->print_message($command[0]);
      // Print command if we are debugging
      if ($this->config['debug']) $this->print_message($command[1]);
      // Execute the current command and store results
      exec($command[1] . ' 2>&1', $output, $error);
      // Error while executing the command
      if ($error) $this->throw_error('Error: ' . implode(' ', $output), 500);
    }

    // Print that the deployment was successful
    $this->print_message('Deployment successful.');
  }

  /*
  * Private Functions
  */

  // Print a message in the standard format
  private function print_message ($message) {
    // Print the message
    echo '<p>' . $message . '</p>' . "\r\n";
  }

  // Throw an error with an optional message and response code
  private function throw_error ($message='', $code) {
    // Print the message
    echo '<p>' . $message . '</p>' . "\r\n";
    // Set the response code
    http_response_code($code);
    // Terminate the script
    exit();
  }

  // Check to see if the request is valid
  private function valid_request () {
    // Secret access token is given
    if ($this->config['secret']) {
      // Secret access token signature not given, invalid request
      if (!array_key_exists('HTTP_X_HUB_SIGNATURE', $_SERVER)) return false;
      // Secret access token signature does not match expected signature, invalid request
      if ($_SERVER['HTTP_X_HUB_SIGNATURE'] != 'sha1=' . hash_hmac('sha1', file_get_contents('php://input'), $this->config['secret'])) return false;
    }
    // Valid request
    return true;
  }
}
?>
