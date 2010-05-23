<?php

include_once "{$GLOBALS['temovico']['framework_root']}/CurlWebService.php";
include_once "{$GLOBALS['temovico']['framework_root']}/Model.php";
include_once "{$GLOBALS['temovico']['framework_root']}/Logger.php";

/** 
 * Dummy twitter service
 **/
class TwitterService extends CurlWebService { 
  
  public static function get_tweets_for_username($username) {
    $path = "statuses/user_timeline/{$username}.json";
    $results = json_decode(self::singleton()->call($path), $assoc = true);
    $tweets = array();
    foreach ($results as $result) {
      $tweets[] = new Tweet($result);
    }
    return $tweets;
  }

}

class Tweet extends Model {
  
  // todo: maybe make this data-driven and have it dynamically define instance vars based on the result so you don't have to declare all the attributes by hand like this:
  
  protected $id;
	protected $in_reply_to_user_id;
	protected $contributors;
	protected $created_at;
	protected $user_id;
	protected $source;
	protected $geo;
	protected $place;
	protected $in_reply_to_screen_name;
	protected $truncated;
	protected $coordinates;
  protected $favorited;
  protected $in_reply_to_status_id;
  protected $text;
	protected $user; // a submodel 
	
	public function __construct($array) {
	  // TODO, make having a submodel be declarative instead of being dependent on custom constructor.
	  if (array_key_exists('user', $array)) {
	    $array['user'] = new TwitterUser($array['user']);
	  }
	  parent::__construct($array);
	}
	
}

class TwitterUser extends Model {
  protected $profile_text_color;
  protected $description;
  protected $lang;
  protected $profile_image_url;
  protected $created_at;
  protected $profile_link_color;
  protected $followers_count;
  protected $statuses_count;
  protected $time_zone;
  protected $screen_name;
  protected $following;
  protected $friends_count;
  protected $profile_sidebar_fill_color;
  protected $contributors_enabled;
  protected $url;
  protected $notifications;
  protected $profile_background_image_url;
  protected $favourites_count;
  protected $profile_sidebar_border_color;
  protected $protected;
  protected $location;
  protected $geo_enabled;
  protected $profile_background_tile;
  protected $name;
  protected $profile_background_color;
  protected $id;
  protected $verified;
  protected $utc_offset;
}