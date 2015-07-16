<?php

// NOTE - Remove this in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

// Deployer class
class Deployer {

  /*
  * Variables
  */

  // Configuration array
  protected $config = array();
  // Output array
  protected $output = array();

  /*
  * Constructors
  */

  // Main constructor
  public function __construct ($config) {
    // Default configuration
    $default = array(
      // Default repository url
      'remote' => 'https://github.com/Swiper-CCCVI/php-github-deploy.git',
      // Default local target directory
      'target' => '/var/www/default',
      // Default repository branch
      'branch' => 'master',
      // Default local temporary directory
      'temp' => '/tmp/deploy',
      // Default secret access token
      'secret' => FALSE,
      // Default exclude file name
      'exclude' => '.exclude'
    );
    // Initialize the configuration array
    $this->config = array_merge($default, $config);
  }

  /*
  * Private Functions
  */

  // Add a message for output
  private function add_output ($type, $message) {
    // Add the message and type to the output array
    $this->output[] = array($type, $message);
  }

  // Check if the incoming request is valid
  private function valid_request () {
    // If the secret access token is set
    if ($this->config['secret']) {
      // Log the debugging message
      $this->add_output('debug', 'Secret access token specified');
      // Request providing GitHub style authentication
      if (array_key_exists('HTTP_X_HUB_SIGNATURE', $_SERVER)) {
        // Log the debugging message
        $this->add_output('debug', 'Request provided GitHub style authentication');
        // Request authentication is invalid
        if ($_SERVER['HTTP_X_HUB_SIGNATURE'] != 'sha1=' . hash_hmac('sha1', file_get_contents('php://input'), $this->config['secret'])) {
          // Log the error message
          $this->add_output('error', 'ERROR: Request authentication invalid');
          // Return FALSE
          return FALSE;
        }
      }
      // Request providing generic style authentication
      else if (array_key_exists('secret', $_GET)) {
        // Log the message
        $this->add_output('debug', 'Request provided URL style authentication');
        // Request authentication is invalid
        if ($_GET['secret'] != $this->config['secret']) {
          // Log the error message and terminate
          $this->add_output('error', 'ERROR: Request authentication invalid');
          // Return FALSE
          return FALSE;
        }
      }
      // Request providing no authentication
      else {
        // Log the error message and terminate
        $this->add_output('error', 'ERROR: Request provided no authentication');
        // Return FALSE
        return FALSE;
      }
    }
    // If the secret acces token is not set
    else {
      // Log the debugging message
      $this->add_output('warning', 'WARNING: Secret access token not specified');
    }
    // Request is valid if this point reached, log the debugging message
    $this->add_output('debug', 'Request authentication valid');
    // Return TRUE
    return TRUE;
  }

  /*
  * Public Functions
  */

  // Echo the debug output to the response
  public function echo_output () {
    // Echo the CSS for the output
    ?>
    <style>
      body {
        background-color: #222222;
      } p {
        font-weight: bold;
      } .debug {
        color: cyan;
      } .warning {
        color: yellow;
      } .error {
        color: red;
      } .success {
        color: green;
      }
    </style>
    <?php
    // Loop through all output messages
    foreach ($this->output as $message) {
      // Echo the message with it's type as the class
      echo "<p class='$message[0]'>$message[1]</p>\n";
    }
  }

  // Log the output to a file
  public function log_output ($logfile) {
    // Output string to append
    $output_str = '';
    // Loop through all output messages
    foreach ($this->output as $message) {
      // Add the message and it's type
      $output_str .= $message[0] . ': ' . $message[1] . "\n";
    }
    // If we could not log the output to the specified file
    if (!file_put_contents($logfile, $output_str . "\n", FILE_APPEND)) {
      // Log the error message
      $this->add_output('ERROR', 'Could not log output to ' . $logfile);
    }
    // If we logged the output to the specified file
    else {
      // Log the debugging message
      $this->add_output('DEBUG', 'Output logged to ' . $logfile);
    }
  }

  // Deployment function
  public function deploy () {
    // Log the request time
    $this->add_output('debug', 'Request Time: ' . $_SERVER['REQUEST_TIME']);
    // Log the request host
    $this->add_output('debug', 'Request Host: ' . $_SERVER['HTTP_HOST']);

    // If the request is not valid
    if (!$this->valid_request()) {
      // Set the HTTP response code
      http_response_code(403);
      // Return FALSE
      return FALSE;
    }

    // Array of commands and their messages
    $commands = array();

    // If the temporary directory exists
    if (file_exists($this->config['temp'])) {
      // Add the command to remove the temporary directory
      $commands[] = array(
        'Removing the temporary directory',
        sprintf('rm -rf %s', $this->config['temp'])
      );
    }

    // Add the command to create the temporary directory
    $commands[] = array(
      'Creating the temporary directory',
      sprintf('mkdir %s', $this->config['temp'])
    );

    // Add the command to clone the repository files into the temporary directory
    $commands[] = array(
      'Cloning the repository files into the temporary directory',
      sprintf('git clone --branch %s %s %s', $this->config['branch'], $this->config['remote'], $this->config['temp'])
    );

    // Add the command to sync the temporary directory to the target directory
    $commands[] = array(
      'Syncing the target directory with the temporary directory',
      sprintf('rsync -a -r --delete --delete-excluded --filter=\'dir-merge,-n /%s\' %s %s', $this->config['exclude'], $this->config['temp'] . '/', $this->config['target'])
    );

    // Add the command to remove the temporary directory
    $commands[] = array(
      'Removing the temporary directory',
      sprintf('rm -rf %s', $this->config['temp'])
    );

    // Loop through each command and their messages
    foreach ($commands as $command) {
      // Output the command message
      $this->add_output('debug', $command[0]);
      // Output the command
      $this->add_output('debug', '> ' . $command[1]);
      // Execute the command and store the result
      exec($command[1] . ' 2>&1', $output, $error);
      // Error while executing command
      if ($error) {
        // Output the command error
        $this->add_output('error', 'ERROR: ' . implode(' ', $output));
        // Set the HTTP response code
        http_response_code(500);
        // Return FALSE
        return FALSE;
      }
    }

    // Output the success message
    $this->add_output('success', 'Deployment successful');
    // Set the HTTP response code
    http_response_code(200);
    // Return TRUE
    return TRUE;
  }
}

?>
