<?php

// Require the deployer class
require_once 'php-deployer.php';

$config = array(
  // Repository URL
  'remote' => 'https://github.com/Swiper-CCCVI/php-github-deploy.git',
  // Local target directory
  'target' => '/var/www/default',
  // Repository branch
  'branch' => 'master',
  // Local temporary directory
  'temp' => '/tmp/deploy',
  // Secret access token
  'secret' => 'yoursecretaccesstoken',
  // Exclude file name
  'exclude' => '.exclude'
);

// Initialize the deployer
$deployer = new Deployer($config);
// Deploy the deployer
$deployer->deploy();

// Log the deployer output
$deployer->log_output('/var/log/deploy.log');
// Echo the deployer output
$deployer->echo_output();

?>
