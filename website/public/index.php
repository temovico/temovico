<?php

$configFileRelativePath = '../conf/website.conf.php';
$configFile = realpath(dirname(__FILE__) . '/' . $configFileRelativePath);
if (!file_exists($configFile)) {
  echo "couldn't find config file at $configFileRelativePath. Exiting";
}

include_once $configFile;

session_start();

include_once "{$GLOBALS['temovico']['config']['framework_root']}/Dispatcher.php";
$dispatcher = new Dispatcher();
$dispatcher->dispatch();

?>