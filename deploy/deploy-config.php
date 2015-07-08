<?php
// Configuration as a serialized array
define('CONFIG', serialize(array(
  // Repository url
  'remote' => 'https://github.com/Swiper-CCCVI/php-github-deploy.git',
  // Local target directory
  'target' => '/var/www/php-github-deploy',
  // Repository branch
  'branch' => 'master',
  // Local temporary directory
  'temp' => '/tmp/php-github-deploy',
  // Log file location
  'logfile' => '/var/log/php-github-deploy.log',
  // Secret access token
  'secret' => 'yoursecretaccesstoken',
  // Debug print flag
  'debug' => TRUE,
  // Log print flag
  'log' => TRUE
)));
?>
