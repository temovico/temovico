<?php

/**
 * Cache provides a unified interface to the memcache servers. 
 * Wrapper around the PECL memcache package. 
 * See http://pecl.php.net/package/memcache for package information.
 * See http://www.php.net/manual/en/ref.memcache.php for 
 * function documentation.
 **/
class Cache {
    
  private static $singleton = null;
  private $enabled = null;
  
  private $memcache;
  
  /** Record of all gets and sets made through Cache. **/
  public static $gets = array();
  public static $sets = array();
  
  public static function singleton() {
    if (!isset(self::$singleton)) {
      self::$singleton = new Cache();  
    }
    return self::$singleton;
  }
  
  private function __construct() {
    $this->memcache = new memcache();
    $this->enabled = $GLOBALS['temovico']['memcache']['enabled'];
    if ($this->enabled) {
      foreach($GLOBALS['temovico']['memcache']['servers'] as $server) {
        list($host, $port) = split(':', $server);
        if ($this->memcache->addServer($host, $port)) {
          Logger::info("Cache added memcache server: $server");
        } else {
          Logger::error("Cache failed to add memcache server: $server");          
        }
      }
    }
  }
  
  public function delete($key) {
    if (!$this->enabled) {
      return false;
    }
    $success = $this->memcache->delete($key);
    if ($success) {
      Logger::info("Cache deleted memcache key: $key");
    } else {
      Logger::error("Cache failed to delete memcache key: $key");
    }
    return $success;
  }

  public function set($key, $obj, $expire = 0) {
    if (!$this->enabled) {
      return false;
    }
    $success = $this->memcache->set($key, $obj, 0, $expire); 
    if ($success) {
      Logger::info("Cache stored memcache key: $key");
    } else {
      Logger::notice("Cache failed to store memcache key: $key");
    }
    Cache::$sets[] = array('key' => $key, 'expire' => $expire, 'success' => $success);
    return $success;
  }
  
  // $key can be a single key or an array of keys
  public function get($key) {
    if (!$this->enabled) {
      return null;
    }
    $result = $this->memcache->get($key);
    Cache::$gets[] = array('key' => $key, 'success' => ($result !== false));
    return $result;
  }
  
  public function increment($key, $value = 1) {
    return $this->memcache->increment($key, $value);
  }
  
  // i wish this would work but apparently static and instance methods share the same namespace in php.. 
  // at least they used to... maybe in new php it works now
  // public static function delete($key) { self::singleton()->delete($key); }
  // public static function set($key, $obj, $expire = 0) { self::singleton()->set($key, $obj, $expire); }
  // public static function get($key) { self::singleton()->get($key); }
    
}

?>
