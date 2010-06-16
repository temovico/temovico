<?php 

include_once "{$GLOBALS['temovico']['framework_root']}/Model.php";
include_once "{$GLOBALS['temovico']['framework_root']}/MySQLDatabaseService.php";

/**
 * MySQLModel
 *
 * @package Framework
 *
 * MySQLModel is a MySQL backed model
 * 
 *
 **/
class MySQLModel extends Model {
    
  protected $_new_record;

  public function is_new_record() {
    return $this->_new_record;
  }
  
  public function set_is_old_record() {
    $this->_new_record = false;
  }
  
  public function __construct($params) {
    parent::__construct($params);
    $this->_new_record = true;
  }

  public static function database() {
    return MySQLDatabaseService::get($GLOBALS['temovico']['mysql']['default']);
  }

  public static function database_table_name() {
    return strtolower(get_called_class());
  }
  
  public static function create($array) {
    $classname = get_called_class();
    $instance = new $classname($array);
    $instance->save();
    return $instance;
  }
  

  public static function find($primary_key) {
    $find_all = self::find_by(
      $attribute = static::primary_key_name(), 
      $value = $primary_key
    );
		return $find_all[0];
  }
  
  public static function find_all($where = null) {
    $results = self::database()->select(
      $fields = '*',
      $from = self::database_table_name(),
      $where = array('1 = 1')
    );
    return static::instantiate_all($results);
  }
  
  public static function find_by($attribute, $value) {
    $results = self::database()->select(
      $fields = '*',
      $from = static::database_table_name(),
      $where = array("$attribute = ?", $value)
    );
    return self::instantiate_all($results);
  }
  
  public static function instantiate_all($results) {
    $classname = get_called_class();
    $instances = array();
    foreach ($results as $result) {
      $instances[] = static::instantiate($result);
    }
		return $instances;
  }

	public static function instantiate($result) {
		$classname = get_called_class();
		$instance = new $classname($result);
		$instance->set_is_old_record();
		return $instance;
	}
  
  public static function __callStatic($method_name, $args) {
    $classname = get_called_class();
    if (method_exists($classname, $method_name)) {
      return call_user_func(array($classname, $method_name));      
    } elseif (str_starts_with($method_name, 'find_by_')) {
      $attribute = substr($method_name, 8);
      return static::find_by($attribute, $args[0]);
    } 
  }
  
  /**
   * Returns the primary key for this table
   *
   * @return string
   */
  public static function primary_key_name() {
    return 'id';
  }

  public function primary_key() {
    if ($this->primary_key_name()) {
      return $this->get_attribute(self::primary_key_name());
    } else {
      return null;
    } 
  }
  
  public function primary_key_where_array() {
    return array(static::primary_key_name() . ' = ?', $this->primary_key());
  }

  public function save() {
    if (property_exists($this, 'updated_at')) {
      $this->updated_at = 'NOW()';
    }
  
    if ($this->is_new_record()) {
      if (property_exists($this, 'created_at')) {
        $this->created_at = 'NOW()';
      }
      $set_hash = $this->attributes_hash();
    
      if (self::primary_key_name()) {
        unset($set_hash[static::primary_key_name()]);
      }
    
      $result = self::database()->insert(
        $table = static::database_table_name(), 
        $set_hash
      );
      
      $this->id = $result;
      $this->_new_record = false;
    
    } else {
      $result = self::database()->update(
        $table = static::database_table_name(),
        $set_hash = $this->attributes_hash(),
        $where = $this->primary_key_where_array()
      );
    }
  
    return $result;
  }

  public function delete() {
    if ($this->primary_key()) {
      return $this->database()->delete(
        $table = static::database_table_name(),
        $where = $this->primary_key_where_array()
      );
    } else {
      throw new MySQLDatabaseException("no primary key defined in mysql model.. can't delete");
    }
  }
}