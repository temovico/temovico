<?php

include_once "{$GLOBALS['temovico']['website_root']}/conf/website.conf.php";

/**
 * The Big Red Buttons lets us turn off different parts of a site.
 */
class BigRedButton {

  private $features;
  
  /**
  * Loads brb.conf file and sets up features
  */
  function __construct() {
    $this->features = $GLOBALS['temovico']['brb']['features'];
  }

  /**
  * Returns true if a feature is on and false if it's off. Defaults to on if not present in the array
  */
  function is_enabled($feature) {
    return array_key_exists($feature, $this->features) ? $this->features[$feature] : true;
  }
  
}

?>
