<?php

include_once "{$GLOBALS['temovico']['config']['framework_root']}/Controller.php";

class HomeController extends Controller {
    
    public function __construct($params) {        
        parent::__construct($params);
    }
    
    public function index() {
      $this->username = array_key_exists('username', $this->params) ? $this->params['username'] : null;
    }
    
    
}

?>