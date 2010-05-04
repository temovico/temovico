<?php

class Stopwatch {

  private $start_time;
  
  public function __construct() {
    $this->start();
  }

  public function start() {
    $this->start_time = microtime(true);
  }
  
  public function stop() {
    $elapsed = microtime(true) - $this->start_time;
    return sprintf('%0.3f', $elapsed);
  }
}

?>
