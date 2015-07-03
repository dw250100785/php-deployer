<?php
// Require the GitHub Deployer class
require_once 'github-deployer.php';

// Initialize the deployer instance with some configuration
$deployer = new GithubDeployer(array(
  // The ssh url for the remote repository to deploy
  'remote' => 'git@github.com:yourusername/yourrepository.git',
  // The secret access token to authenticate requests by
  'secret' => 'yoursecretaccesstoken',
  // The local target directory to deploy the repository files to
  'target' => '/var/www/www.yourwebsite.com'
));

// Deploy the repository on authenticated requests
$deployer->deploy();
?>
