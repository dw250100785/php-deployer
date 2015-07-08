<?php

/*
* Configuration
*/

// Require the configuration
require_once 'deploy-config.php';

// Default configuration
$GLOBALS['config'] = array();
// Default log array
$GLOBALS['log'] = array();

// Configuration defined
if (defined('CONFIG')) {
  // Unserialize the configuration
  $GLOBALS['config'] = unserialize(CONFIG);
  // Invalid configuration
  if (!is_array($GLOBALS['config'])) {
    // Log a message
    $GLOBALS['log'][] = 'Error: Configuration must be a valid serialized array.';
    $GLOBALS['log'][] = '';
    // Terminate
    terminate(400);
  }
}

// Merge specified configuration with default configuration
$GLOBALS['config'] = array_merge(array(
  // Repository url
  'remote' => 'https://github.com/Swiper-CCCVI/php-github-deploy.git',
  // Local target directory
  'target' => '/var/www/default',
  // Repository branch
  'branch' => 'master',
  // Local temporary directory
  'temp' => '/tmp/php-github-deploy',
  // Log file location
  'logfile' => '/var/log/php-github-deploy.log',
  // Secret access token
  'secret' => FALSE,
  // Debug print flag
  'debug' => FALSE,
  // Log print flag
  'log' => FALSE
), $GLOBALS['config']);

/*
* Functions
*/

// Terminate the program with a response code
function terminate ($code) {
  // If logging is enabled
  if ($GLOBALS['config']['log']) {
    // Put the log array in the log file
    $result = file_put_contents($GLOBALS['config']['logfile'], str_repeat('#', 10) . "\n" . implode("\n", $GLOBALS['log']) . "\n");
    // Error while trying to put data into log file
    if (!$result) {
      // Add a message
      $GLOBALS['log'][] = 'Could not log output to '. $GLOBALS['config']['logfile'];
      $GLOBALS['log'][] = '';
    }
    // Data was successfully put into log file
    else {
      // Add a message
      $GLOBALS['log'][] = 'Output logged to '. $GLOBALS['config']['logfile'];
      $GLOBALS['log'][] = '';
    }
  }
  // If debugging is enabled
  if ($GLOBALS['config']['debug']) {
    // Print the log array
    echo implode("\n", $GLOBALS['log']);
  }
  // Set the response code
  http_response_code($code);
  // Terminate the script
  exit();
}

// Execute and handle a command
function cmd($command) {
  // Log the command
  $GLOBALS['log'][] = 'Command: ' . $command;
  // Execute the command and store the result
  exec($command . ' 2>&1', $output, $error);
  // Error while executing command
  if ($error) {
    // Log command error output
    $GLOBALS['log'][] = 'Error: ' . implode(' ', $output);
    $GLOBALS['log'][] = '';
    // Terminate
    terminate(500);
  }
  // Command executed successfully with output
  else if (!empty($output)) {
    // Log command output
    $GLOBALS['log'][] = 'Output: ' . implode(' ', $output);
    $GLOBALS['log'][] = '';
  }
  // Command executed successfully without output
  else {
    // Log that there was no output
    $GLOBALS['log'][] = 'Output: N/A';
    $GLOBALS['log'][] = '';
  }
}

/*
* Deployment
*/

// Log the current date and time
$GLOBALS['log'][] = 'Datetime: ' . date('Y-m-d H:i:s');
$GLOBALS['log'][] = '';

// Log the configuration
$GLOBALS['log'][] = var_export($GLOBALS['config'], TRUE);
$GLOBALS['log'][] = '';

// Log a starting message
$GLOBALS['log'][] = 'Starting deployment.';
$GLOBALS['log'][] = '';

// If the secret access token is set
if ($GLOBALS['config']['secret']) {
  // Secret access token signature was not passed
  if (!array_key_exists('HTTP_X_HUB_SIGNATURE', $_SERVER)) {
    // Log a message
    $GLOBALS['log'][] = 'Error: Missing secret access token signature.';
    $GLOBALS['log'][] = '';
    // Terminate
    terminate(403);
  }
  // Produce the signature that we expect to see
  $expected = 'sha1=' . hash_hmac('sha1', file_get_contents('php://input'), $GLOBALS['config']['secret']);
  // Exit with a message if secret access token signature does not match what we expected
  if ($_SERVER['HTTP_X_HUB_SIGNATURE'] != $expected) {
    // Log a message
    $GLOBALS['log'][] = 'Error: Invalid secret access token signature.';
    $GLOBALS['log'][] = '';
    // Terminate
    terminate(403);
  }
}

// Remove the temporary directory
$GLOBALS['log'][] = 'Deleting old temporary directory.';
cmd(sprintf('rm -rf %s', $GLOBALS['config']['temp']));

// Create the temporary directory
$GLOBALS['log'][] = 'Creating new temporary directory.';
cmd(sprintf('mkdir %s', $GLOBALS['config']['temp']));

// Clone the repository into the target directory
$GLOBALS['log'][] = 'Cloning repository files into temporary directory.';
cmd(sprintf('git clone --branch %s %s %s', $GLOBALS['config']['branch'], $GLOBALS['config']['remote'], $GLOBALS['config']['temp']));

// If there is a new deployment exclude list that we can use
if (file_exists($GLOBALS['config']['temp'].'/deploy/.deploy-exclude')) {
  // Sync the temporary directory to the target directory while respecting the new deployment exclude list
  $GLOBALS['log'][] = 'Exclude list found in repository, syncing target directory with respect to new exclude list.';
  cmd(sprintf('rsync -a --delete --delete-excluded --exclude-from=%s %s %s', $GLOBALS['config']['temp'].'/deploy/.deploy-exclude', $GLOBALS['config']['temp'].'/', $GLOBALS['config']['target']));
}
// If there is not a deployment exclude file that we can use
else {
  // Sync the temporary directory to the target directory
  $GLOBALS['log'][] = 'Exclude list not found in repository, syncing target directory normally.';
  cmd(sprintf('rsync -a --delete %s %s', $GLOBALS['config']['temp'].'/', $GLOBALS['config']['target']));
}

// Remove the temporary directory
$GLOBALS['log'][] = 'Deleting temporary directory.';
cmd(sprintf('rm -rf %s', $GLOBALS['config']['temp']));

// Log a success message
$GLOBALS['log'][] = 'Deployment successful.';
$GLOBALS['log'][] = '';
// Terminate
terminate(200);

?>
