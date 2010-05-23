<?php

error_log("got here");
include_once "{$GLOBALS['temovico']['framework_root']}/Controller.php";
include_once "{$GLOBALS['temovico']['framework_root']}/Logger.php";
include_once "{$GLOBALS['temovico']['website_root']}/models/User.php";

class HomeController extends Controller {
    
  public function __construct($params) {        
    parent::__construct($params);
  }
  
  public function index() {
  }
  
  public function users() {
    $this->users = User::find_all();    
  }
  
  public function user() {
    $this->user = User::find_by_username($this->params['username']);
    $this->tweets = $this->user->tweets();
  }
  
  public function create() {
    if ($this->request_method() == 'POST') {
      $this->user = new User($this->params['user']);
      $this->user->save();
      $this->redirect_to('/users');
    }
  }
  
  public function delete() {
    $user = User::find_by_username($this->params['username']);
    $user->delete();
    $this->redirect_to('/users');
  }
   
}

?>