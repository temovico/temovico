<?php

include_once "{$GLOBALS['temovico']['config']['framework_root']}/Logger.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/Stopwatch.php";

abstract class DataService {
    
  protected $stopwatch;
  protected $last_request_time;
  protected $last_query_info;
  protected $last_result_info;
  
  // Record of all service calls (array of arrays keyed by service name)
  protected static $calls = array();
  
  public function requesting($query_info) {
    if (!isset($this->stopwatch)) {
      $this->stopwatch = new Stopwatch();
    }
    $this->last_query_info = $query_info;
    $this->stopwatch->start();
    $this->log($query_info);
  }
  
  public function received($result_info) {
    $this->last_request_time = $this->stopwatch->stop();
    $result_info['Request time'] = $this->last_request_time . 'ms';  
    $this->last_result_info = $result_info;  
    $this->log($result_info);
    
    // Log call data to internal array
    $call = array_merge($this->last_query_info, $this->last_result_info);

    if ($GLOBALS['temovico']['config']['dev_mode']) {
      $call['backtrace'] = debug_backtrace();
    }

    self::$calls[] = $call;
  }
  
  private function log($messages) {
    $message = '';
    foreach ($messages as $k => $v) {
      if (is_array($v)) {
        $v = print_r($v, TRUE);
      }
      $message .= "$k: $v\n";
    }
    Logger::debug($message, 'service');
  }
  
  public static function calls() {
    return self::$calls;
  }
    
}

class DataServiceException extends Exception {
  public $code = '';
  
  public function __construct($message = '', $code = null) {
    parent::__construct($message);
    $this->code = $code;
  }
}

?>