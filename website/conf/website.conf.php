<?php

// TODO: maybe build a wrapper around this so user can do Config::param('key', 'nested key', 'more nested key').. not sure if that's worth it or cool...

// php.ini stuff here
date_default_timezone_set("America/Los_Angeles");
mb_internal_encoding('UTF-8');
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

$TEMOVICO_WEBSITE_ROOT = realpath(dirname(__FILE__) . '/../');
$TEMOVICO_WEBSITE_NAME = 'my website';

$GLOBALS['temovico']['config'] = array(
  
  'website_root' => $TEMOVICO_WEBSITE_ROOT,
  'framework_root' => realpath(dirname(__FILE__) . '/../../temovico/'),
  'website_name' => $TEMOVICO_WEBSITE_NAME,
  'salt' => "HELLO_WEB_USER!",
  'response_types' => array('json', 'xml', 'rss', 'html', 'csv'),
  
  // log
  'log' => array(
    'filepath' => "/tmp/temovico_website.log",
    'priority' => 'DEBUG' // ALERT > CRITICAL > ERROR > WARNING > NOTICE > INFO > DEBUG
  ),
  
  'mysql' => array(
    'databases' => array(
      'website' => array(
        'database' => 'website',
        'host' => 'localhost',
        'username' => 'root',
        'password' => ''
      )
    ),
    'default' => 'website'
  ),
  
  'web_services' => array(
    'twitter' => array(
    )
  ),
  
  'log_post_and_get_arrays' => false,
  
  // render, service and db timing
  'timing_metrics' => true,  
  
  // debugging & development output on screen
  'dev_mode' => array (
    'enabled' => false 
  ),
  
  'static_dirs' => array(
    'javascripts' => 'js',
    'stylesheets' => 'css',
    'images' => 'images'
  ),
  
  'memcache' => array(
    'enabled' => true,
    'servers' => array(
      '127.0.0.1:11111',
      '127.0.0.1:22222'
    )
  ),
  
  'service' => array(
    'user_agent' => "$TEMOVICO_WEBSITE_NAME",
    'services' => array(
    
    )
  ),
  
  'brb' => array(
    'live' => '/etc/temovico/brb.conf', 
    'fullsite_html' => "{$TEMOVICO_WEBSITE_ROOT}/conf/full_brb.html",
    'features' => array(
      'fullsite' => true,
      'sessions' => true,
      'home' => true
    )
  ),
);

// have to look for php directive so that we can do this and take out the mention of the $GLOBALS['temovico']['routes'] in that file.. 
// $GLOBALS['temovico']['routes'] = include("{$GLOBALS['temovico']['config']['website_root']}/conf/routes.conf.php");

include("{$GLOBALS['temovico']['config']['website_root']}/conf/routes.conf.php");


// Full site BRB
$live_brb = $GLOBALS['temovico']['config']['brb']['live'];
if (file_exists($live_brb)) {
  include_once $live_brb;
  $GLOBALS['temovico']['config']['brb']['features'] = array_merge(
    $TEMOVICO_LIVE_BRB_FEATURES, // this is the array defined in the live brb file
    $GLOBALS['temovico']['config']['brb']['features']
  );
}

if (!$GLOBALS['temovico']['config']['brb']['features']['fullsite']) {
  $fullsite_brb_html = $GLOBALS['temovico']['config']['brb']['fullsite_html'];
  if (file_exists($fullsite_brb_html)) {
    include_once $fullsite_brb_html;
  }
  exit;
}

if ($GLOBALS['temovico']['config']['dev_mode']) {
  // include dev mode stuff... need to port
}

?>
