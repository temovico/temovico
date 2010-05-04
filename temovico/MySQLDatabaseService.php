<?php 
/**
 * MySQLDatabaseService class for talking directly to mysql databases from php 
 *
 * MySQLDatabaseService provides a means to get commonly used databases and a sql generating layer for making select, 
 * insert, update, and delete queries as well as raw sql queries.
 * @package Service
 */
 
 // TODO: decide whether to use true/false or exceptions for error conditions

include_once "{$GLOBALS['temovico']['config']['framework_root']}/DataService.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/Logger.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/functions.php";
 
class MySQLDatabaseService extends DataService {
  
  // Singleton instances of this class are stored in this array based on name
  protected static $instances = array();
  
  // database connection details
  protected $database_name;    
  protected $host;
  protected $username;
  protected $password;
  
  // The MySQL link identifier returned by mysql_connect
  protected $link;
  
  /**
   * Static singleton accessor  
   * Used to return a database service singleton connected with the details specified in config
   * @param string $database_name name name of a database specified in config
   * @return MySQLDatabaseService
   *
   * @example usage
   * $db = MySQLDatabaseService::get('database_name');
   */
  public static function get($database_name) {
    if (array_key_exists($database_name, $GLOBALS['temovico']['config']['mysql']['databases'])) {
      if (!array_key_exists($database_name, self::$instances)) {
        $connect_info = $GLOBALS['temovico']['config']['mysql']['databases'][$database_name];
        self::$instances[$database_name] = new MySQLDatabaseService(
          $connect_info['database'], 
          $connect_info['host'],
          $connect_info['username'],
          $connect_info['password']
        );
      }
      $instance = self::$instances[$database_name];
      return $instance;
    } else {
      throw new MySQLDatabaseException("No connection details for database named [$database_name]");  
    }
  }
  
  /**
   * Constructor
   * Private because databases should be added to $_dbConnectInfo as needed and accessed through ::get
   * @param string $database_name database name 
   * @param string $host database host 
   * @param string $username database username
   * @param string $password database password
   * @return void
   */
  public function __construct($database_name, $host, $username, $password) {
    $this->database_name = $database_name;
    $this->host = $host;
    $this->username = $username;
    $this->password = $password;
    $this->connect();
  }
  
  /** 
   * Connect to the database $this represents
   * Called by __construct
   * @return void
   */
  private function connect() {
    // i'm assuming we don't want persistent mysql connections, but if we do, we could use:
    // $link = @mysql_pconnect($this->host, $this->username, $this->password);
    $link = @mysql_connect($this->host, $this->username, $this->password);
    if (!$link || !is_resource($link) || !@mysql_select_db($this->database_name, $link)) {
      $error_msg = "Connect to {$this->database_name}@{$this->host} as {$this->username} failed: " . mysql_error() . " " . mysql_errno();
      Logger::error($error_msg, 'SQLDatabaseService');
      throw new MySQLDatabaseException($error_msg);
    }
    $this->link = $link;
    Logger::info("Connect to {$this->database_name}@{$this->host} succeeded", 'MySQLDatabaseService');
  }
  
  /** 
   * Disconnect from the database $this represents 
   * @return void
   */
  private function disconnect() {
    mysql_close($this->link);
  }
  
  public function link() {
    return $this->link;  
  }
  
  /**
   * Do raw SQL query
   * This is delegated to by the sql generating functions (insert, update, delete, select) and can also be called externally
   * @param string $sql SQL to query
   * @param string $query_type, specified when this is being delegated to by insert, update, delete and select
   * @return mixed result of the query: new id on insert, true on successful update/delete, assoc result on select
   *
   * @todo better mysql_fetch_* usage
   */
  public function query($sql, $query_type = null) {
    // adding this because i had issues where different instances on the same host had the same 
    // link and that link would point to the most recently instantiated database. 
    // subsequent queries to the earlier instance would have the more recent database selected
    $sql = squeeze($sql); // remove extra spaces
    @mysql_select_db($this->database_name, $this->link); 
    $sql = trim($sql);
    Logger::info("query starting [$sql]", 'MySQLDatabaseService');
    
    $this->requesting(array(
      'service' => 'MySQLDatabaseService',
      'database' => $this->database_name,
      'host' => $this->host,
      'username' => $this->username,
      'SQL' => $sql
    ));
    
    $query_result = @mysql_query($sql, $this->link);
    if ((mysql_error() != "") || (mysql_errno() != 0)) { // maybe just errno != 0 ?
      $error = mysql_error();
      $errno = mysql_errno();
      throw new MySQLDatabaseException("MySQL Error #$errno: $error [$sql]");
    }

    $this->received(array(
      'result' => print_r($query_result, TRUE)
    ));
  
    if (!$query_result) {
      return null;
    }
    
    $result = array();
    if (!$query_type) {
      $query_type = self::determine_query_type($sql);
    }
    
    switch($query_type) {
      case 'INSERT':
        $result = mysql_insert_id($this->link);
        break;
      case 'SELECT':
        // TODO: improve this to smartly use other mysql_fetch_* funcs
        while ($row = @mysql_fetch_assoc($query_result)) {
          $column_names = array_keys($row);
          $row_data = array();
          foreach ($column_names as $column_name) {
            $row_data[] = $row[$column_name];
          }
          $result[] = $row;
        }
        break;
      case 'UPDATE':
        $result = true;
        break;
      case 'DELETE':
        $result = true;
        break;
      default:
        throw new InvalidSQLException($sql);
    }
    return $result;
  }
  
  /**
   * Make insert query
   * INSERT INTO $table (<$insertHash keys>) VALUES (<$insertHash values>)
   * @param string $table table to insert into
   * @param array $insert_hash of column name keyed to value
   * @return integer of new record id on success.. throws exception on failure
   */
  public function insert($table, $insert_hash) {
    $num_fields = count($insert_hash);
    if ($num_fields == 0) {
      throw new InvalidSQLException('insert_hash is empty');
    }
    $i = 1;
    $insert_fields = '';
    $insert_values = '';
    foreach($insert_hash as $insert_field => $insert_value) {
	    $insert_fields .= "`$insert_field`";
	    $insert_value = $this->escape($insert_value);
	    $insert_values .= $insert_value;
	    if ($i < $num_fields) { 
        $insert_fields .= ',';
        $insert_values .= ',';
	    }
	    $i++;
  	}
  	$insertFields = "($insert_fields)";
  	$insertValues = "($insert_values)";
    $sql = "INSERT INTO $table $insert_fields VALUES $insert_values";
    return $this->query($sql, 'INSERT');
  }
  
  /**
   * Make select query
   * SELECT $fields FROM $tables [WHERE $where] [$extras]
   * @param string $fields SQL fragment of fields to select
   * @param string $tables SQL fragment of tables to select form
   * @param string $where optional SQL fragment of where clause
   * @param string $extras optional hash for making [GROUP BY $group_column [HAVING $group_conditions]] [ORDER BY $sort_columns] [LIMIT $limit] [OFFSET $offset]
   * @return array result of select query
   *
   * @example usage
   * select( 
   *   $columns  = '*',
   *   $from = 'tablename',
   *   $where = "blah='something' AND blah2='something else'";
   *   $extras = array(
   *     'ORDER BY' => 'blah',
   *     'LIMIT' => 50
   *   );
   * )
   */
  public function select($columns, $tables, $where = '', $extras = '') { 	
    // TODO: determine how reasonable it is to assume these checks aren't needed...
    // $columns = $this->escape($columns, $in_quotes = FALSE);
	  // $tables = $this->escape($tables, $in_quotes = FALSE);
		$sql = "SELECT $columns FROM $tables";
		if ($where) {
		  $where = $this->make_where_clause($where);
		  $sql = "$sql WHERE $where";
	  }
    if ($extras) {
      $possible_extras = array('GROUP BY', 'ORDER BY', 'LIMIT', 'OFFSET');
  	  foreach ($possible_extras as $possible_extra) {
  	    if (array_key_exists($possible_extra, $extras)) {
  	      $extra_value = $this->escape($extras[$possible_extra], $in_quotes = FALSE);
  	      $sql = "$sql $possible_extra $extra_value";
  	      if (($possible_extra == 'GROUP BY') && array_key_exists('HAVING', $extras)) {
  	        //need internal check for having since it is subordinate to GROUP BY being present
  	        $having_value = $this->escape($extras['HAVING'], $in_quotes = FALSE);
  	        $sql = "$sql HAVING $having_value";
  	      }
  	    }
  	  }
    }
		return $this->query($sql, 'SELECT');
  }
  
  /**
   * Make select query and return single result.
   * This is like select but it returns the first result
   * SELECT $fields FROM $tables [WHERE $where] [$extras]
   * @param string $fields SQL fragment of fields to select
   * @param string $tables SQL fragment of tables to select form
   * @param string $where optional SQL fragment of where clause
   * @param string $extras optional hash for making [GROUP BY $group_column [HAVING $group_conditions]] [ORDER BY $sort_columns] [LIMIT $limit] [OFFSET $offset]
   * @return array result of select query
   */
  public function select_one($columns, $tables, $where = '', $extras = '') {
    if (!$extras) { 
      $extras = array();
    }
    $extras['LIMIT'] = 1;
    $result = $this->select($columns, $tables, $where, $extras);
    if ($result) {
      $result = $result[0];
    }
    return $result;
  }
  
  /**
   * Make update query
   * UPDATE $table SET $set_hash_key1=$set_hash_val1, ... WHERE $where
   * @param string $table table to update
   * @param array $set_hash column names keyed to value
   * @param string $where SQL fragment of where clause
   * @return boolean true on success, false on failure
   */
  //TODO: maybe change to update($table, $set_hash, $where, $orderBy = '', $limit = '') { } ??
  public function update($table, $set_hash, $where) {
    // is it reasonable to assume this isn't needed?
	  // $table = $this->escape($table, $inQuotes = FALSE);
    $numFields = count($set_hash);
    if ($numFields == 0) {
      throw new InvalidSQLException('Error, set hash is empty');
    }
  	$i = 1;
  	$sets = '';
  	foreach($set_hash as $set_field => $set_value) {
	    $set_value = $this->escape($set_value);
	    $sets .= "`$set_field` = $set_value";
	    if ($i < $num_fields) {
	        $sets .= ', ';
	    }
	    $i++;
  	}
    $where = $this->make_where_clause($where);
    $sql = "UPDATE $table SET $sets WHERE $where";
    return $this->query($sql, 'UPDATE');
  }
  
  /**
   * Make update query
   * DELETE FROM $table USING $using WHERE $where
   * @param string $table table to delete from
   * @param string $where SQL fragment of where clause
   * @param string $using optional SQL fragment for table references
   * @return boolean true on success, false on failure
   */
  public function delete($table, $where, $using = '') {
    $where = $this->make_where_clause($where);
    if (!$where) {
      throw MySQLDatabaseException('cannot delete with empty where clause');
    }
    $using = $using ? "USING $using" : '';
    $sql = "DELETE FROM $table $using WHERE $where";
    return $this->query($sql, 'DELETE');
  }
  
  public function set_unicode(){
  	$sql = "SET NAMES 'utf8'";
  	$this->query($sql);
  } 
  
  /**
   * Return an array of all the tables in a database
   * @param string $sql SQL to determine query type of
   * @return string|boolean one of 'INSERT', 'SELECT', 'UPDATE', 'DELETE', FALSE
   */
  public function list_tables() {
    $tables = array();
    $tableResult = mysql_list_tables($this->database_name, $this->link);
    while ($row = mysql_fetch_row($table_result)) {
      $tables[] = $row[0];
    }
    return $tables;
  }
  
  /**
   * Determine query type of SQL statement
   * @param string $sql SQL to determine query type of
   * @return string|boolean one of 'INSERT', 'SELECT', 'UPDATE', 'DELETE', FALSE
   */
  private static function determine_query_type($sql) {
    $valid_query_types = array('INSERT', 'SELECT', 'UPDATE', 'DELETE');
    $queryType = substr($sql, 0, strpos($sql, ' '));
    if (in_array($queryType, $valid_query_types)) {
      return strtoupper($query_type);
    } else {
      return false;
    }
  }
  
  /**
   * Make a safely escaped where fragment of SQL out of a plain string or a rails-style 
   * conditions statement like: array("username=? AND user_id IN ?", $username, array(1, 2, 75, 200))
   * @param string $sql SQL to determine query type of
   * @return string|boolean one of 'INSERT', 'SELECT', 'UPDATE', 'DELETE', FALSE
   */
  private function make_where_clause($where) {
    if (is_array($where)) {
      $token = '?'; // todo: move to define()
      $safe_token = '?SAFE_TOKEN?';
      $where_text = array_shift($where);
      // replace ? tokens with a rarer token ?SAFE_TOKEN? to allow ?s in the actual content.. TODO: move to define()
      $where_text = str_replace($token, $safe_token, $where_text);
      $num_wheres = substr_count($where_text, $safe_token);
      if ($num_wheres != count($where)) {
        throw new InvalidSQLException("WHERE mismatch:expected $num_wheres got " . count($where));
      }
      for($i = 0; $i < $num_wheres; $i++) {
        $where_text = str_replace_first($safe_token, $this->make_where_val($where[$i]), $where_text);
      }
      $where = $where_text;
    } else {
      $where = $this->escape($where, $in_quotes = FALSE);
      //unescape single quotes so we can pass in strings like "blah != 'something'"
		  $where = str_replace("\'", "'", $where);
    }
    return $where;
  }

  /**
   * Make a safely escaped where value out of a string or an array 
   * conditions statement like: array("username=? AND user_id IN ?", $username, array(1, 2, 75, 200))
   * @param string|array $where_val value to be escape
   * @return string escape where value
   */
  private function make_where_val($where_val) {
  	if (is_array($where_val)) {
	    foreach($where_val as &$val) {
	      $val = $this->escape($val);
	    }
      $value = '(' . implode(',', $where_val) . ')';	
    } else {
  		$value = $this->escape($where_val);
    }
  	return $value;
  }
  
  /** 
   * Safely escape string for sql taking into account SQL literals
   * @param string $val string to be escaped and quoted
   * @return string mysql escaped and quoted string
   */
  public function escape($val, $in_quotes = TRUE) {
    $sql_literals = array('NOW()'); // TODO: add more here, DUH... and move to DEFINE
    if (is_numeric($val) || in_array(strtoupper($val), $sql_literals)) {
      return $val;
    }  
    if (get_magic_quotes_gpc()) {
      $val = stripslashes($val);
    }
    $val = mysql_real_escape_string($val, $this->link);
    if ($in_quotes) {
      $val = "'$val'";
    }
    return $val;
  }
  
  /** 
   * Return the number of rows affected by the last query
   * @return integer
   */
  public function num_affected_rows() {
    return mysql_affected_rows($this->link);
  }
}

class MySQLDatabaseException extends Exception {}
class InvalidSQLException extends MySQLDatabaseException {}
class NoSQLDatabaseResultsException extends MySQLDatabaseException {}

?>