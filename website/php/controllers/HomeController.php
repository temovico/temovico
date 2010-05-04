<?php


include_once "{$GLOBALS['temovico']['config']['framework_root']}/Controller.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/Logger.php";
include_once "{$GLOBALS['temovico']['config']['website_root']}/php/models/User.php";

class HomeController extends Controller {
    
  public function __construct($params) {        
    parent::__construct($params);
  }
  
  public function index() {
    $this->users = User::find_all();      
  }
   
}

?>