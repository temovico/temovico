<?php

include_once "{$GLOBALS['temovico']['framework_root']}/exceptions.php";
include_once "{$GLOBALS['temovico']['framework_root']}/Logger.php";

class Dispatcher {
  private $routes;
  private $params;
  private $path;
  
  public function __construct() {
    $this->routes = $GLOBALS['temovico']['routes'];
    $this->params = array();
  }
  
  public function dispatch() {
    try {
      
      $parsed_request_uri = parse_url($_SERVER['REQUEST_URI']);
      $this->path = $parsed_request_uri['path'];
      Logger::info("Dispatching {$this->path}");
      
      foreach ($this->routes as $route_descriptor => $route_data) {
        $split_descriptor = mb_split('/', $route_descriptor);
        
        if ($this->path_and_descriptor_match($this->path, $split_descriptor)) {          
          Logger::info("'$route_descriptor' matched by {$this->path}", 'dispatch');
          $this->params['controller'] = $route_data[0];
          $this->params['action'] = $route_data[1];
          
          if (sizeof($route_data) == 3) {
            foreach ($route_data[2] as $request_param_name) {
              if (array_key_exists($request_param_name, $_REQUEST)) {
                $this->params[$request_param_name] = $_REQUEST[$request_param_name];
              }
            }
          }

          $controller_file = realpath("{$GLOBALS['temovico']['website_root']}/php/controllers/{$this->params['controller']}Controller.php");
          if (file_exists($controller_file)) {
            require_once $controller_file;
          }
          $classname = "{$this->params['controller']}Controller";
          $controller = new $classname($this->params);
          exit;
        }
      }
      throw new FileNotFoundException($this->path);
      
    } catch (FileNotFoundException $e) {
      #LOGGER.error("{$_SERVER['PATH_INFO']} was not matched in dispatch.php. Returning 404", 'dispatch')
      header("HTTP/1.0 404 Not Found");
      include '404.html';
      exit;
    }
  }
  
  private function path_and_descriptor_match($path, $descriptor) {
    $path = mb_substr($path, 1); //remove leading slash

    $descriptor_size = sizeof($descriptor);

    for ($i = 0; $i < $descriptor_size ; $i++) {
      $descriptor_token = $descriptor[$i];
      $pre_split_path = $path;
      $next_slash_position = mb_strpos($path, '/');
      
      if ($next_slash_position === FALSE) {
        $path_token = $path;
        $path = '';
      } else {
        $path_token = mb_substr($path, 0, $next_slash_position);
        $path = mb_substr($path, $next_slash_position + 1);
      }
    
      if ($descriptor_token{0} == ':') {
        $param_name = mb_substr($descriptor_token, 1);
        // if its the last one, add the whole path (supports urls as last argument), otherwise just add the token
        // consider remove urls as arg as last arg... 
        if ($i == ($descriptor_size - 1)) {
          if ($pre_split_path) {
            $this->params[$param_name] = $pre_split_path;
          }
        } else {
          $this->params[$param_name] = $path_token;
        }
      } else {
        if ($descriptor_token == $path_token) {
          continue;
        } else {
          $this->params = array();
          return false;
        }
      }
    }

    return true;
  }
}

?>
