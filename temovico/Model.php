<?php

/**
 * Model
 *
 * @package Framework
 *
 * Model is a data source agnostic class used to represent the primary objects in our system
 * 
 *
 **/
class Model {
      
  // dummy set with arrays approach.. to be refined
  public function __construct($obj) {
    foreach ($array as $key => $val) {
      $this->$key = $val;
    }
    $this->_new_record = true;
  }
  
  
  /**
   * Do not call this method. 
   * It is a magic method used to avoid the need for writing getters for every instance variable.
   * $obj->blah ==> $obj->__get('blah')
   * It also allows you to override accessing the instance variable directly by using an instance 
   * method of the same name. To bypass __get magic use getAttribute
   * A side-effect of this is the ability to omit the parentheses from method calls with no arguments:
   * $obj->method_with_no_arguments ==> $obj->method_with_no_arguments()
   *
   * @param string $var The name of the property being accessed
   * @throws NoSuchAttributeException when $var doesn't exist
   * @return mixed
   */
  public function __get($var) {
    $get_method_name = $var;
    if (method_exists($this, $get_method_name)) { 
      return $this->$get_method_name();    
         
    } elseif (property_exists($this, $var)) { 
      return $this->$var;
          
    } else {
      $msg = "Cannot find instance variable named \"$var\"."; 
      throw new NoSuchAttributeException($msg);  
    }
  }
  
  
  /**
   * Do not call this method. 
   * It is a magic method used to avoid the need for writing setters for every instance variable.
   * $obj->blah = 'foo' ==> $obj->__set('blah', 'foo')
   * It also allows you to have a prefilter method for setting the instance variable directly by
   * by using a method of the same name prefaced with the word "set". 
   * so if you had a method named "set_name"
   * $obj->name = 'hello' ==> $obj->__set('name', 'hello') ==> $obj->set_name('hello')
   *
   * @param string $var The name of the property being set
   * @param string $val The value of the property being set
   * @throws IllegalModificationException when $var is not setable
   * @throws NoSuchAttributeException when $var doesn't exist
   * @return void
   */
  public function __set($var, $val) {
    if (property_exists($this, $var)) {
      $set_method_name = "set_" . $var;
      if (method_exists($this, $set_method_name)) {
        $this->$set_method_name($val);
      } else {
        $this->$var = $val;
      }
        
    } else {
      $msg = "Cannot find instance variable named \"$var\"."; 
      throw new NoSuchAttributeException($msg);
    }  
  }
  
  /**
   * Do not call this method. 
   * It is a magic method used internally to test whether an instance variable is set
   * isset($obj->blah) ==> $obj->__isset('blah')
   *
   * @param string $var The name of the property being unset
   * @throws NoSuchAttributeException when $var doesn't exist
   * @return void
   */
  public function __isset($var) {
    if (property_exists($this, $var)) {
      $object_vars = get_object_vars($this);
      return array_key_exists($var, $object_vars) && isset($object_vars[$var]);

    } else {
      $msg = "Cannot find instance variable named \"$var\"."; 
      throw new NoSuchAttributeException($msg);
    }
  }
  
  /**
   * Returns an array of object instance variable names, excluding those prefaced with '_'
   *
   * @return array
   */
  public function attribute_names() { 
    return array_keys($this->attributes_hash());
  }
  
  /**
   * Returns an associative array of object instance variables keyed to their names, excluding those prefaced with '_'
   * @return array
   */
  protected function attributes_hash() {        
    $instance_vars = array();
    $instance_var_names_and_values = get_object_vars($this);
    foreach ($instance_var_names_and_values as $name => $value) {
      if ($name{0} != '_') {
        $instance_vars[$name] = $value;
      }
    }
    
    return $instanc_vars;
  }
  
  /**
   * Retrieve an instance variable attribute, bypassing __get magic
   *
   * @param string $var The name of the property being accessed
   * @return mixed
   */
  public function get_attribute($var) {
    $object_vars = get_object_vars($this);
    return $object_vars[$var];
  }
  
  /**
   * Sets an attribute using __set
   * 
   * @param string $var The name of the property being set
   * @param string $val The value of the property being set
   * @return void
   */
  public function set_attribute($var, $val) {
    $this->__set($var, $val);
  }
  
  /**
   * Check whether this class has an instance variable with this name
   *
   * @param string $var The name of the property being checked for
   * @return bool
   */
  public function has_attribute($var) {
    return property_exists($this, $var);
  }
  
  /**
   * Used for turning a model object into an associative array with a subset of its attributes, where space is an issue. 
   *
   * @param Variable size list of the attributes to include
   * @return array
   */
  public function summarize() {
    $attribute_names = func_get_args();
    return array_filter_by_keys($this->attributes_hash(), $attribute_names);
  }
  
  /**
   * Convert this object into a string that holds its JSON representation
   *
   * @return string
   */
  public function __toString() {
    return json_encode($this->attributes_hash());
  }
    
}

class NoSuchAttributeException extends Exception { }

?>
