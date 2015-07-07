<?php

/*
* Documentation
*/

// Response Codes
// 200 - Server deployment successful
// 400 - Invalid client configuration
// 403 - Invalid request authentication
// 500 - Error while trying to deploy

// Configuration Keys
// REMOTE   Required  Default: N/A                Remote repository url to synch target directory with.
// TARGET   Required  Default: N/A                Local directory to synch remote repository files to.
// BRANCH   Optional  Default: master             Remote repository branch to synch target directory with.
// TEMP     Optional  Default: /tmp/md5(REMOTE)/  Temporary directory to pull files to before synching.
// SECRET   Optional  Default: FALSE              Secret access token used to authenticate all requests.
// DEBUG    Optional  Default: FALSE              Flag to indicate the output of debugging information.
// LOG      Optional  Default: FALSE              Log file location used to store deployment logs.

/*
* Configuration
*/

// Configuration file validation
if (!file_exists('config.php')) { terminate('Configuration file required.', 400); }
require_once 'config.php';

// Configuration defaulting
if (!defined('REMOTE')) { terminate('REMOTE configuration parameter required.', 400); }
if (!defined('TARGET')) { terminate('TARGET configuration parameter required.', 400); }
if (!defined('BRANCH')) { define('BRANCH', 'master'); }
if (!defined('TEMP'))   { define('TEMP', '/tmp/' . md5(REMOTE)); }
if (!defined('SECRET')) { define('SECRET', FALSE); }
if (!defined('IGNORE')) { define('IGNORE', FALSE); }
if (!defined('DEBUG'))  { define('DEBUG', FALSE); }
if (!defined('LOG'))    { define('LOG', FALSE); }

// Configuration validation
if (!is_string(REMOTE))                       { terminate('REMOTE configuration parameter must be of type string.', 400); }
if (!is_string(TARGET))                       { terminate('TARGET configuration parameter must be of type string.', 400); }
if (!is_string(BRANCH))                       { terminate('BRANCH configuration parameter must be of type string.', 400); }
if (!is_string(TEMP))                         { terminate('TEMP configuration parameter must be of type string.', 400); }
if (SECRET && !is_string(SECRET))             { terminate('SECRET configuration parameter must be of type string or FALSE.', 400); }
if (IGNORE && !is_array(unserialize(IGNORE))) { terminate('IGNORE configuration parameter must be of type array or FALSE.', 400); }
if (!is_bool(DEBUG))                          { terminate('DEBUG configuration parameter must be of type boolean.', 400); }
if (LOG && !is_string(LOG))                   { terminate('LOG configuration parameter must be of type string or FALSE.', 400); }

/*
* Functions
*/

// Terminate the program with a response code
function terminate ($message=FALSE, $code) {
  // If a message is given, add it
  if ($message) { message($message); }
  // If the debug flag is true
  if (defined('DEBUG') && DEBUG === TRUE) {
    // Print all of the messages
    foreach ($GLOBALS['github-deploy-log'] as $message) { echo '<p>' . $message . '</p>' . "\r\n"; }
  }
  // If the log file location is set
  if (defined('LOG') && is_string(LOG)) {
    // Write the current date and time
    file_put_contents(LOG, date('Y-m-d H:i:s') . '\n');
    // Write all of the messages
    foreach ($GLOBALS['github-deploy-log'] as $message) { file_put_contents(LOG, $message . '\n'); }
    // Write an empty line
    file_put_contents(LOG, '\n');
  }
  // Set the response code
  http_response_code($code);
  // Terminate the script
  exit();
}

// Add a new message
function message ($message) {
  // Initialize the array of messages if not done
  if (!isset($GLOBALS['github-deploy-log'])) { $GLOBALS['github-deploy-log'] = array(); }
  // Add the given message
  $GLOBALS['github-deploy-log'][] = $message;
}

/*
* Deployment
*/

// Print a starting message
message('Starting deployment.');

// If the secret access token is set
if (SECRET) {
  // Exit with a message if secret access token signature was not passed
  if (!array_key_exists('HTTP_X_HUB_SIGNATURE', $_SERVER)) { terminate('Invalid request authentication.', 403); }
  // Produce the signature that we expect to see
  $expected = 'sha1=' . hash_hmac('sha1', file_get_contents('php://input'), SECRET);
  // Exit with a message if secret access token signature does not match what we expected
  if ($_SERVER['HTTP_X_HUB_SIGNATURE'] != $expected) { terminate('Invalid request authentication.', 403); }
}

// Format IGNORE for use in rsynch
$ignore = array();
if (IGNORE) { foreach (unserialize(IGNORE) as $i) { $ignore[] = '--exclude=' . $i; } }

// Array of commands
$commands = array(
  // Remove possible temporary directory
  sprintf('rm -rf %s', TEMP),
  // Create the new temporary directory
  sprintf('mkdir %s', TEMP),
  // Clone the repository files into the temporary directory
  sprintf('git clone --branch %s %s %s', BRANCH, REMOTE, TEMP),
  // Synch the target directory with the temporary directory
  sprintf('rsync -a --delete %s %s %s', implode(' ', $ignore), TEMP . '/', TARGET),
  // Remove the temporary directory
  sprintf('rm -rf %s', TEMP)
);

// Loop through each command
foreach ($commands as $command) {
  // Print the command
  message($command);
  // Execute the command
  exec($command . ' 2>&1', $output, $error);
  // Exit with a message on any error
  if ($error) { terminate('Error: ' . implode(' ', $output), 500); }
}

// Terminate with a success message
terminate('Deployment successful.', 200);
?>
