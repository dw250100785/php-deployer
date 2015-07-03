<?php
require_once 'github-deployer.php';
$deployer = new GithubDeployer(array(
  'remote' => 'git@github.com:yourusername/yourrepository.git',
  'secret' => 'yoursecretaccesstoken',
  'target' => '/var/www/www.yourwebsite.com'
));
$deployer->deploy();
?>
