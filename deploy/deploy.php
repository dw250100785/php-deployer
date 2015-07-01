<?php
// Require the PHP GitHub Deploy class
require_once 'php-github-deploy.php';
// Initialize a PHP GitHub Deploy object
$php_github_deploy = new PHP_GitHub_Deploy(array(
  // Your remote repository ssh url
	'remote' => 'git@github.com:yourusername/yourrepository.git',
  // Your secret access token
	'secret' => 'yoursecretaccesstoken',
  // Your target web server directory
	'target' => '/var/www/www.yourwebsite.com'
));
// Deploy the PHP GitHub Deploy object
$php_github_deploy->deploy();
?>
