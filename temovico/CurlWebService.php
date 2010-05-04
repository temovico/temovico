<?php

include_once "{$GLOBALS['temovico']['config']['framework_root']}/Logger.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/DataService.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/functions.php";

class CurlWebService extends DataService {

    protected $curl;
    protected $curl_info;
    protected $curl_errno;
    protected $curl_error;
    
    protected $base_url;  //i.e. www.youtube.com:80/api2_rest
    protected $url;
    protected $connect_timeout; // in ms
    protected $timeout; // in ms
    protected $headers;

    protected $result;
    
    
    // TODO: make this name+config driven.. 
    public function __construct($base_url, $connect_timeout = 5000, $timeout = 15000) {
      parent::__construct();  
      
      $this->base_url = $base_url;
      $this->connect_timeout = $connect_timeout;
      $this->timeout = $timeout;
      
      $this->headers = array();
    }
    
    public function add_headers($headers) {
      if (!is_array($headers)) {
        $headers = array($headers);
      }
      $this->headers = array_merge($this->headers, $headers);
    }
    
    public function set_timeouts($connect_timeout, $timeout) {
      $this->connect_timeout = $connect_timeout;
      $this->timeout = $timeout; 
    }
    
    public function set_connect_timeout($connect_timeout) {
      $this->connect_timeout = $connect_timeout;
    }
    
    public function set_timeout($timeout) {
      $this->timeout = $timeout;
    }
    
    public function call($path, $get_params = null, $post_params = null) {
        
      $curl = curl_init();
      
      // maybe move this to the constructor?
      if (!$curl) {
        Logger::error("Can't initialize curl", array('curlservice', 'service'));
        throw new CurlWebServiceException("Can't initialize curl");
      }
      
      $this->url = "{$this->base_url}/{$path}";
              
      if ($get_params) {
        $this->url = $this->url . build_query_string($get_params);
      }

      $curl_options = array(
        CURLOPT_URL                 => $this->url,
        CURLOPT_FOLLOWLOCATION      => true,
        CURLOPT_HEADER              => 0,
        CURLOPT_RETURNTRANSFER      => TRUE,
        CURLOPT_USERAGENT           => $GLOBALS['temovico']['config']['service']['user_agent'],
      );
      
      if ($post_params) {
        $curl_options[CURLOPT_POST] = 1;
        $curl_options[CURLOPT_POSTFIELDS] = $post_params;
      }

      if ($this->headers && is_array($this->headers)) {
        $curl_options[CURLOPT_HTTPHEADER] = $this->headers;
      }

      curl_setopt_array($curl, $curl_options);
      // We have to set these here instead of the by passing them with the $curl_options array or else they're ignored...weird
      curl_setopt($curl, CURLOPT_TIMEOUT_MS, $this->timeout);
      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, $this->timeout);
      
      $this->requesting(array(
        'service' => 'CurlWebService',
        'url'  => $this->url,
        'POST' => $post_params
      ));
      
      $this->result = curl_exec($curl);
      $this->curl_info = curl_getinfo($curl);
      $this->curl_errno = curl_errno($curl);
      $this->curl_error = curl_error($curl);

      // Deal with errors
      // For a list of curl error codes, see http://curl.haxx.se/libcurl/c/libcurl-errors.html 
      if ($this->curl_errno > 0) {
        $result_info = array(
          'HTTP Code' => $this->http_code(),
          'curl errno' => $this->curl_errno,
          'curl error' => $this->curl_error
        );
        switch ($this->curl_errno) {
          // TODO: add handling for more errors
          case 28: 
            // It's a timeout
            $result_info['message'] = "Curl timed out on {$this->url} with \$connect_timeout = {$this->connect_timeout}ms, \$timeout = {$this->timeout}ms";
            $this->received($result_info);
            throw new CurlWebServiceException($message, $this->url, $this->curl_errno, $this->curl_error);
              
          default:
            // Catchall for other errors
            $result_info['message'] = "Curl error on {$this->url}: ({$this->curl_errno}) {$this->curl_error}";
            $this->received($result_info);
            throw new CurlWebServiceException($message, $this->url, $this->curl_errno, $this->curl_error);
        }
      } else {
        $this->received(array(
          'HTTP Code' => $this->http_code(),
          'result' => $this->result
        ));
      }

      curl_close($curl);

      return $this->result;
    }
    
    public function result() { return $this->result; }  
    public function last_request_time() { return $this->last_request_time; }  
    public function content_type() { return $this->get_from_curl_info('content_type'); }
    public function http_code() { return $this->get_from_curl_info('http_code'); }
    
    private function get_from_curl_info($key) {
      if ($this->curl_info && array_key_exists($key, $this->curl_info)) {
        return $this->curl_info[$key];
      }
    }
    
    public static function calls() {
      return array_key_exists('CurlWebService', self::$calls) ? self::$calls['CurlWebService'] : array();
    }
}

class CurlWebServiceException extends DataServiceException {

    public $message; 
    public $url;
    public $errno;
    public $error;
    
    public function __construct($message, $url = null, $errno = null, $error = null) {
      $this->message = $message;
      $this->url = $url;
      $this->errno = $errno;
      $this->error = $error;
    }
}

?>
