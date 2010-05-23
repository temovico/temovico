<?php

include_once "{$GLOBALS['temovico']['framework_root']}/MySQLDatabaseService.php";
include_once "{$GLOBALS['temovico']['framework_root']}/MySQLModel.php";
include_once "{$GLOBALS['temovico']['website_root']}/php/models/TwitterService.php";

class User extends MySQLModel {  
    
	protected $id;
	protected $username;
	protected $created_at;

  public function created_at() {
    return strtotime($this->get_attribute('created_at')); 
  } 

  public function tweets() {
    return TwitterService::get_tweets_for_username($this->username);
  }

}
 
?>
