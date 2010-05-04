<?php 

include_once "{$GLOBALS['temovico']['config']['framework_root']}/Model.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/MySQLDatabaseService.php";

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
  protected $database;  

  public function new_record() {
    return $this->_new_record;
  }

  public static function database() {
    return MySQLDatabaseService::get($GLOBALS['temovico']['config']['mysql']['default']);
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
    return self::find_by(
      $attribute = self::primary_key_name(), 
      $value = $primary_key
    );
  }
  
  public static function find_all() {
    $results = self::database()->select(
      $fields = '*',
      $from = self::database_table_name(),
      $where = array('1 = 1')
    );
    return self::instantiate_all($results);
  }
  
  public static function find_by($attribute, $value) {
    $results = self::database()->select(
      $fields = '*',
      $from = self::database_table_name(),
      $where = array("$attribute = ?", $value)
    );
    return self::instantiate_all($results);
  }
  
  public static function instantiate_all($results) {
    $classname = get_called_class();
    Logger::debug("CLASSNAME is $classname");
    $instances = array();
    foreach ($results as $result) {
      $instance = new $classname($result);
      $instances[] = $instances;
    }
    return $instances;
  }
  
  public static function __callStatic($method_name, $args) {
    $classname = get_called_class();
    if (method_exists($classname, $method_name)) {
      return call_user_func(array($classname, $method_name));
      
    } elseif (str_starts_with($method_name, 'find_by_')) {
      $attribute = substr($method_name, 8);
      return self::find_by($attribute, $args[0]);
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
    return array("{$self::primary_key_name()} = ?", $this->primary_key());
  }

  public function save() {
    if (property_exists($this, 'updated_at')) {
      $this->updated_at = 'NOW()';
    }
  
    if ($this->new_record()) {
      if (property_exists($this, 'created_at')) {
        $this->created_at = 'NOW()';
      }
      $set_hash = $this->attributes_hash();
    
      if (self::primary_key_name()) {
        unset($set_hash[self::primary_key_name()]);
      }
    
      $result = self::database()->insert(
        $table = self::database_table_name(), 
        $set_hash
      );
      
      $this->id = $result;
      $this->_new_record = false;
    
    } else {
      $result = self::database()->update(
        $table = self::database_table_name(),
        $set_hash = $this->attributes_hash(),
        $where = $this->primary_key_where_array()
      );
    }
  
    return $result;
  }

  public function delete() {
    if ($this->primary_key()) {
      return $this->database()->delete(
        $table = self::database_table_name(),
        $where = $this->primary_key_where_array()
      );
    } else {
      throw new MySQLDatabaseException("no primary key defined in mysql model.. can't delete");
    }
  }
}