<?php

// php.ini stuff here
date_default_timezone_set("America/Los_Angeles");
mb_internal_encoding('UTF-8');
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

$TEMOVICO_WEBSITE_ROOT = realpath(dirname(__FILE__) . '/../');

$GLOBALS['temovico']['config'] = array(
  
  // log
  'log' => array(
    'filepath' => "/tmp/temovico_website.log",
    'priority' => 'DEBUG' // ALERT > CRITICAL > ERROR > WARNING > NOTICE > INFO > DEBUG
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
    
  'salt' => "HELLO_WEB_USER!",
    
  'timing_metrics' => true,  // render & db timing
  'dev_mode' => false, // debugging & development output on screen
  
  'website_root' => $TEMOVICO_WEBSITE_ROOT,
  'framework_root' => realpath(dirname(__FILE__) . '/../../temovico/'),
  'website_name' => 'My rad website',
  
  'response_types' => array('json', 'xml', 'rss', 'html', 'csv'),
  
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
