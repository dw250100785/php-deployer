<?php

/*
* Configuration
*/

// Require the configuration
require_once 'deploy-config.php';

// Default configuration
$GLOBALS['config'] = array();
// Initialize the log array
$GLOBALS['log'] = array();

// Configuration defined
if (defined('CONFIG')) {
  // Unserialize the configuration
  $GLOBALS['config'] = unserialize(CONFIG);
  // Invalid configuration
  if (!is_array($GLOBALS['config'])) {
    // Log a message
    $GLOBALS['log'][] = 'Error: Configuration must be a valid serialized array.';
    // Terminate
    terminate(400);
  }
}

// Merge specified configuration with default configuration
$GLOBALS['config'] = array_merge(array(
  'remote' => 'https://github.com/Swiper-CCCVI/php-github-deploy.git',
  'target' => '/var/www/default',
  'branch' => 'master',
  'temp' => '/tmp/php-github-deploy',
  'logfile' => '/log/php-github-deploy.log',
  'secret' => FALSE,
  'debug' => FALSE,
  'log' => FALSE
), $GLOBALS['config']);

/*
* Functions
*/

// Terminate the program with a response code
function terminate ($code) {
  // If debugging is enabled
  if ($GLOBALS['config']['debug']) {
    // Print the log array
    echo implode("\r\n", $GLOBALS['log']);
  }
  // If logging is enabled
  if ($GLOBALS['config']['log']) {
    // Put the current date and time in the log file
    file_put_contents($GLOBALS['config']['logfile'], date('Y-m-d H:i:s') . "\r\n");
    // Put the log array in the log file and add a line of space from the next entry
    file_put_contents($GLOBALS['config']['logfile'], implode("\n", $GLOBALS['log']) . "\r\n\r\n");
  }
  // Set the response code
  http_response_code($code);
  // Terminate the script
  exit();
}

function cmd($command) {
  $GLOBALS['log'][] = $command;
  exec($command . ' 2>&1', $output, $error);
  if ($error) {
    $GLOBALS['log'][] = 'Error: ' . implode(' ', $output);
    terminate(500);
  }
}

/*
* Deployment
*/

// Log a starting message
$GLOBALS['log'][] = 'Starting deployment.';

// Log the set configuration
$GLOBALS['log'][] = var_dump($GLOBALS['config']);

// If the secret access token is set
if ($GLOBALS['config']['secret']) {
  // Secret access token signature was not passed
  if (!array_key_exists('HTTP_X_HUB_SIGNATURE', $_SERVER)) {
    // Log a message
    $GLOBALS['log'][] = 'Error: Missing request secret access token signature.';
    // Terminate
    terminate(403);
  }
  // Produce the signature that we expect to see
  $expected = 'sha1=' . hash_hmac('sha1', file_get_contents('php://input'), $GLOBALS['config']['secret']);
  // Exit with a message if secret access token signature does not match what we expected
  if ($_SERVER['HTTP_X_HUB_SIGNATURE'] != $expected) {
    // Log a message
    $GLOBALS['log'][] = 'Error: Invalid request secret access token signature.';
    // Terminate
    terminate(403);
  }
}

// Remove the temporary directory
cmd(sprintf('rm -rf %s', $GLOBALS['config']['temp']));

// Create the temporary directory
cmd(sprintf('mkdir %s', $GLOBALS['config']['temp']));

// Clone the repository into the target directory
cmd(sprintf('git clone --branch %s %s %s', $GLOBALS['config']['branch'], $GLOBALS['config']['remote'], $GLOBALS['config']['temp']));

// Remove files from the temporary directory according to the new .ignore file
$ignore_handle = fopen($GLOBALS['config']['temp'] . '/' . dirname($_SERVER['SCRIPT_NAME']) . '/deploy-ignore', 'r');
if ($ignore_handle) {
  while (($line = fgets($ignore_handle)) !== FALSE) {
    cmd(sprintf('rm -rf %s', $GLOBALS['config']['temp'] . '/' . trim($line)));
  }
  fclose($ignore_handle);
}

// Sync the temporary directory to the target directory
cmd(sprintf('rsync -a --delete %s %s', $GLOBALS['config']['temp'] . '/', $GLOBALS['config']['target']));

// Remove the temporary directory
cmd(sprintf('rm -rf %s', $GLOBALS['config']['temp']));

// Log a success message
$GLOBALS['log'][] = 'Deployment successful.';
// Terminate
terminate(200);

?>
