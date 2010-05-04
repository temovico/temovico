<?php

include_once "{$GLOBALS['temovico']['config']['framework_root']}/MySQLDatabaseService.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/MySQLModel.php";

class User extends MySQLModel {  
    
	protected $id;
	protected $name;
	protected $username;
	protected $created_at;
           
  public function __construct($array) {
    foreach($array as $key => $val) {
      $this->$key = $val;
    }
  }

  public function created_at() {
    return strtotime($this->get_attribute('created_at')); 
  } 

  public static function is_valid_username($username) {
    return preg_match("/^[a-zA-Z0-9]+$/", $username);
  }    
    
  public function sessionize() {
    $attributes = $this->summarize(array('id', 'name', 'username'));
    foreach ($attributes as $name => $value) {
      $_SESSION[$name] = $value;
    } 
  }
}
 
?>
