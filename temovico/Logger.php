<?php

class Logger {
    
  // Priority ala syslog
  static $LEVELS = array(
    'EMERGENCY' =>  0, // system is unusable
    'ALERT' =>      1, // action must be taken immediately 
    'CRITICAL' =>   2, // critical conditions 
    'ERROR' =>      3, // error conditions 
    'WARNING' =>    4, // warning conditions
    'NOTICE' =>     5, // normal but significant condition
    'INFO' =>       6, // informational
    'DEBUG' =>      7, // debug-level messages
  );
  
  static $instance = null;
  
  private $priority;
  private $filepath;
  
  private function __construct() {
    $this->priority = self::$LEVELS['DEBUG'];
    $this->filepath = '/var/log/website.log';  #. date("Ymd", time()) ?
    
    if (array_key_exists('log', $GLOBALS['temovico']['config'])) {
      $log_config = $GLOBALS['temovico']['config']['log'];
      
      if (array_key_exists('priority', $log_config)) {
        $this->priority = self::$LEVELS[$log_config['priority']];
      }
      
      if (array_key_exists('filepath', $log_config)) {
        $this->filepath = $log_config['filepath'];
      }
    }   
  }
  
  public static function instance() {
    if (!self::$instance) {
      self::$instance = new Logger();
    }
    return self::$instance;
  }
  
  private function log($msg, $priority, $tags = null) {
    // only log if the priority requested is less or equal to config logging priority
    $priority_name = $priority;
    $priority = self::$LEVELS[$priority_name];
    if ($priority <= $this->priority) {
      $timestamp = strftime("%Y-%m-%d %T");
      $tag_string = '';
      if ($tags) {
        $tags = is_array($tags) ? $tags : array($tags);
        while ($tags) {
          $tag = array_shift($tags);
          $tag_string .= "#{$tag}";
          if ($tags) {
            $tag_string .= ' ';
          }
        }
      }
      if ($tag_string) {
        $tag_string = ":{$tag_string}";
      }
      $logline = "{$timestamp}\t[{$priority_name}{$tag_string}]\t{$msg}\n";
      error_log($logline, 3, $this->filepath);
    }
  }
  
  public static function emergency($msg, $tags = null) { self::instance()->log($msg, 'EMERGENCY', $tags); }
  public static function alert($msg, $tags = null) { self::instance()->log($msg, 'ALERT', $tags); }
  public static function critical($msg, $tags = null) { self::instance()->log($msg, 'CRITICAL', $tags); }
  public static function error($msg, $tags = null) { self::instance()->log($msg, 'ERROR', $tags); }
  public static function warning($msg, $tags = null) { self::instance()->log($msg, 'WARNING', $tags); }
  public static function notice($msg, $tags = null) { self::instance()->log($msg, 'NOTICE', $tags); }
  public static function info($msg, $tags = null) { self::instance()->log($msg, 'INFO', $tags); }
  public static function debug($msg, $tags = null) { self::instance()->log($msg, 'DEBUG', $tags); }
  
  public function __call($functionName, $args) {
    $priority = strtoupper($functionName);
    if (array_key_exists($priority, self::$LEVELS)) {
      $this->log($msg = $args[0], $priority, $tags = $args[1]);
    }
  }    
}

?>