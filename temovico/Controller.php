<?

include_once "{$GLOBALS['temovico']['config']['framework_root']}/View.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/BigRedButton.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/functions.php";
include_once "{$GLOBALS['temovico']['config']['framework_root']}/Logger.php";

/**
* Controller
*
* @package Framework
*
* Controller is our request handling layer. Controllers get invoked from dispatch.php and interact with 
* Model objects to manipulate and prepare data for rendering by View
* 
*
**/
abstract class Controller {

  protected $_view;
  protected $_response_type;
  protected $_brb;

  protected $params;
  protected $controller_name;
  protected $action;

  /**
  * This is where all the work is dispatched from during the lifespan of the request for a given page.
  * The PageRender object is set up, js and css files are added, the method correspond to the action
  * is called. Controller instance variables are added to pageRender, and then finally the template
  * and layout are determined and the page is rendered. 
  *
  * @param array $params An array of params pulled from the url and $_REQUEST
  * @param bool $check_brb If true (default) then check whether BigRedButton has this controller 
  *                       enabled. If false, then skip the BRB check.
  * @return void
  */
  function __construct($params, $check_controller_brb = TRUE) {    
    // We use $params to determine whether the controller is new or old. If params is 
    // passed, its new, so we should run the following logic. If its not passed, then 
    // it's old and that logic is getting performed in the child controller, so we needn't
    // do anything here. 
    if (!$params) {
      return;
    }

    try {
      $this->params = $params;
      $this->controller_name = strtolower($params['controller']);
      $this->action = $params['action'];

      Logger::info("Running {$params['controller']}Controller#{$this->action} with params:\n"  . print_r($params, true), $this->controller_name);
      
      if (false) { // TODO: have configuration in mainsite.conf.php to change whether these are shown
        Logger::debug(
          "\$_REQUEST:\n" . print_r($_REQUEST, true) . "\n" .
          "\$_POST:\n" . print_r($_POST, true) . "\n" .
          "\$_GET:\n" . print_r($_GET, true),
          $this->controller_name
        );
      }
      
      

      $this->_brb = new BigRedButton();

      // Create the View for this Controller
      $this->_view = new View($this->controller_name);
      $this->_view->title = $GLOBALS['temovico']['config']['website_name'];
      $this->_view->stylesheets = array("{$this->controller_name}.css");
      $this->_view->javascripts = array("{$this->controller_name}.js");

      if ($check_controller_brb) {
        // If this Controller is BRB'd then render the BRB page and exit
        if (!$this->_brb->is_enabled($this->controller_name)) {
          $this->_view->render('brb');
          exit;
        }
      }

      $this->_new_flash = array();
      if (array_key_exists('flash', $_SESSION)) {
        $this->_old_flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
      } else {
        $this->_old_flash = array();
      }

      $method = $this->action;
      $this->$method(); // call the instance method corresponding to the action

      if ($this->_new_flash) {
        $_SESSION['flash'] = $this->_new_flash;
      }

      $this->_view->set_data('flash', $this->flash());

      // Make controller instance variables (that aren't preceeded by _) automatically 
      // available in the view templates
      $instanceVars = get_object_vars($this);
      foreach ($instanceVars as $key => $val) {
        if (strpos($key, '_') === FALSE) {
          $this->_view->set_data($key, $val);
        }
      }

      include_once "{$GLOBALS['temovico']['config']['website_root']}/php/helpers/{$this->controller_name}.php";

      /////////////////////////////////
      // PREPARE AND RENDER THE VIEW //
      /////////////////////////////////

      // DETERMINE RESPONSE TYPE
      if ($this->is_ajax_request()) {
        $response_type = 'json'; 
      } else {
        $response_type = 'html';
      }
      
      // allow override with $this->params['format']
      $response_types = $GLOBALS['temovico']['config']['response_types'];
      if (array_key_exists('format', $this->params) and in_array($this->params['format'], $response_types)) {
        $response_type =  $this->params['format'];
      }

      // DETERMINE THE TEMPLATE
      $template = ($response_type == 'html') ? $this->action : "{$this->action}_{$response_type}";

      // DETERMINE THE LAYOUT
      $layout = $response_type;

      Logger::debug("Rendering $template within $layout layout");
      $this->_view->render($template, $layout);
      Logger::debug('Done rendering.', $this->controller_name);

    } catch (Exception $e) {
      if ($this->is_ajax_request()) {
        // if it's ajax we ignore it for now
        Logger::error("Ignored exception in {$this->controller_name} ajax request" . print_r($e, true), $this->controller_name);
        $this->send_array_as_json(array('worked' => 'no', 'error' => 'general exception'));
      } else {
        // for debugging on sandbox, 
        Logger::error("Exception in {$this->controller_name}Controller#{$this->action}: " . print_r($e, true), $this->controller_name);
        $this->_view->render('error');
      }
    }
  }

  //////////////////////////////////////////////////////////////
  /// REDIRECTION / RETURN LOCATION SAVING                   ///
  //////////////////////////////////////////////////////////////

  /**
  * Redirect to the path
  *
  * @param string $path Path to redirect to 
  * @return void
  */
  public function redirect_to($path) {
    global $url_override;

    if ($this->_new_flash) {
      $_SESSION['flash'] = $this->_new_flash;
    }

    $location = $url_override . $path;
    header("Location: $location");
    exit;
  }

  /**
  * Save a location for use with redirect_to_return_location later
  *
  * @param string $location to save... optional, if not present location is $_SERVER['REQUEST_URI']
  * @return void
  */
  public function save_return_location($location = null) {
    if (!$location) {
      $location = $_SERVER['REQUEST_URI'];
    }
    $_SESSION['return_location'] = $location;
  }

  public function return_location() {
    if (!array_key_exists('return_location', $_SESSION)) {
      return null;
    }
    return $_SESSION['return_location'];
  }

  /**
  * Redirect to the the previous page that was saved, otherwise
  *
  * @param string $fallback_path Path to redirect to if there isn't a saved previous page
  * @return void
  */
  public function redirect_to_return_location($fallback_path = '/') {
    $return_location = $this->return_location();
    
    if ($return_location and ($return_location != $_SERVER['REQUEST_URI'])) {
      unset($_SESSION['return_location']);
      $this->redirect_to($return_location);
    } else {
      $this->redirect_to($fallback_path);
    }
  }

  //////////////////////////////////////////////////////////////
  /// FLASH                                                  ///
  //////////////////////////////////////////////////////////////

  /**
  * Read from previously saved flash
  * $this->flash('one', 'two', 'three') is functionally equivalent to $this->old_flash['one']['two']['three']
  * and returns null if any of those keys are missing
  *
  * @param mixed Variable length list of arguments that will be used to access old_flash (via func_get_args)
  * @return mixed The result if it is found in flash or null if it isn't
  */
  protected function flash() {
    $current_flash = $this->_old_flash;
    $flash_keys = func_get_args();
    
    if (!$flash_keys) {
      return $current_flash;
    }
    
    foreach ($flash_keys as $key) {
      if (array_key_exists($key, $current_flash) and $current_flash[$key]) {
        $current_flash = $current_flash[$key];
      } else {
        return null;
      }
    }
    
    return $current_flash;
  }

  /**
  * Store a variable in flash
  *
  * @param string $var The name of the variable to store
  * @param string $val The value of the variable to store
  * @return void
  */
  protected function set_flash($var, $val) {
    $this->_new_flash[$var] = $val;
  }

  //////////////////////////////////////////////////////////////
  /// HELPER AND UTILITY FUNCTIONS                           ///
  //////////////////////////////////////////////////////////////

  /**
  * Helper method for checking whether this is an ajax request
  *
  * @return bool
  */
  protected function is_ajax_request() { 
    return (array_key_exists('ajax', $this->params));
  }      

  /**
  * Helper method for getting the request method out of $_SERVER
  * One of ['GET', 'POST', 'PUT', 'DELETE']
  *
  * @return string
  */
  public function request_method() {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if (array_key_exists('_method', $_REQUEST)) {
      $_REQUEST['_method'] = strtoupper($_REQUEST['_method']);
      
      if (in_array($_REQUEST['_method'], array('GET', 'POST', 'PUT', 'DELETE'))) {
        $method = $_REQUEST['_method'];
      }
    }
    
    return $method;
  }

  /**
  * Copy variables out of array into $this->params
  *
  * @param mixed $paramNames either a single name or array of names (keys) of params to copy
  * @param array $array to copy from
  * @return void
  */
  public function extract_to_params($param_names, $array) {
    if (!isset($this->params)) {
      $this->params = array();
    }
    
    if (is_string($param_names)) {
      $param_names = array($param_names);
    }
    
    foreach ($param_names as $param) {
      $this->params[$param] = array_key_exists($param, $array) ? $array[$param] : null;
    }
  }

  /**
  * Echo out a PHP array as JSON
  *
  * @param array $array the array to send as json
  * @return void
  */
  protected function send_array_as_json($array) {
    header('Content-Type: application/javascript'); 
    echo json_encode($array);
  }

  /**
  * A pagination helper, uses two arguments and the page element of $this->params to 
  * setup and determine the page and offset, and create a paginator
  *
  * @param integer $total
  * @param integer $items_per_page
  * @return array($paginator, $page, $offset)
  */
  protected function do_pagination($total, $items_per_page) {
    include_once "{$GLOBALS['temovico']['framework_root']}/Paginator.php";

    $page_count = ceil($total / $items_per_page);
    $paginator = new Paginator($page_count, $items_per_page);

    // Pagination
    if (isset($this->params['page']) && is_numeric($this->params['page'])) {
      $page = $this->params['page'];
      $offset = ($page - 1) * $items_per_page;
    } else {
      $page = 1;
      $offset = 0;
    }

    if ($page > $page_count || $offset > $total) {
      $page = $page_count; 
      $offset = ($page_count - 1) * $items_per_page;
    }
    
    if ($offset < 0) {
      $offset = 0;
    }
    
    //maybe also check for negatives
    return array($paginator, $page, $offset);
  }


  /**
  * A helper to add errors for a given form field
  *
  * @param string $field The field to add an error for
  * @param string $message The error message to display
  * @return void
  */
  protected function add_error($field) {
    if (!isset($this->errors)) {
      $this->errors = array();
    }
    
    if (!array_key_exists($field, $this->errors)) {
      $this->errors[$field] = array();
    }
    
    $this->errors[$field][] = $message;
  }

  /**
  * A helper to check whether a user is logged in 
  *
  * @return boolean Whether a user is logged in
  */
  protected function a_user_is_logged_in() {
    return array_key_exists('user_id', $_SESSION) and is_numeric($_SESSION['user_id']);
  }
}

?>
