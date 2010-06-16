<?

include_once "{$GLOBALS['temovico']['framework_root']}/Logger.php";

/**
 * View
 *
 * @package Framework
 *
 * View accepts data from the controller and renders php templates in that context
 *
 **/

class View {
    
  public $title;
  public $javascripts;
  public $stylesheets;
  public $headers;
  public $meta_description;
  public $feed;
  public $robots;
  public $contentClass;
  
  private $_data  = array();
  private $_controller = array();
  private $_action = array();

  private static $_timestamps;

  /**
   * The constructor sets the base directories in which View will look
   * for templates
   *
   * @param string $controller The controller we're rendering for.
   * @return void
   */
  function __construct($controller) {
    $this->_controller = $controller;
  }

  /**
   * Sets data to be visible in the template
   *
   * @param string $key The name of the variable in the template scope
   * @param mixed $value The value of the variable
   * @return void
   */
  public function set_data($key, $value) {
    if (isset($key)) {
      $this->_data[strval($key)] = $value;
    }
  }

  /**
   * This sets up a link element for a feed associated with the page
   *
   * @param string $feedURL URL of the RSS feed associated with the page
   * @return void
   */
  public function set_feed($feed_url) {
    $this->feed = '<link rel="alternate" type="application/rss+xml" href="' . $feed_url . '" />';
  }
    
  /**
   * Renders a template
   *
   * @param string $page The page name, located at tmpl/$controller/$page.tmpl
   * @param string $layout Wraps the page in a layout at tmpl/layouts/$layout.tmpl
   *                       It inserts the page at $this->yield() in the template 
   *                       and defaults to 'html'
   * @param bool $render_as_string Returns the rendered page as a string if true
   * @return mixed A string of the resulting html if $render_as_string is TRUE, otherwise (by default) void
   */
  public function render($page, $layout = 'html', $render_as_string = FALSE) {
    $this->_page = $page; // in direct (non partial calls) this will be identical to the $action in the controller
      
    if ($layout == null) {
      if ($render_as_string) {
        // if render as string is true, turn on output buffer until done including templates 
        // and retrieve and return rendered template as a string.
        ob_start();
        $this->yield();
        $rendered = ob_get_contents();
        ob_end_clean();
        return $rendered;
          
      } else {
        $this->yield();
        return;
      }
    }
    
    // Bring the data added with setData into scope so it will be visible as
    // local variables when we do the include below
    extract($this->_data);
    
    // Look for layout in /tmpl/layouts/
    // The layout will contain a call back to yield() which will be handled below
    $path = "{$GLOBALS['temovico']['website_root']}/app/views/layouts/{$layout}.html.php";
    if (!is_file($path)) {
      Logger::error("Template not found: $path", array('view'));
      throw new TemplateNotFoundException($path);
    }
    

    if ($render_as_string) {
      // if render as string is true, turn on output buffer until done including templates 
      // and retrieve and return rendered template as a string.
      ob_start();
      include($path);
      $rendered = ob_get_contents();
      ob_end_clean();
      return $rendered;
        
    } else {
      include($path);
    }
  }

  /**
  * Renders the page contents from a layout.
  *
  * @return void
  */
  private function yield() {
    // Bring the data added with setData into scope so it will be visible as
    // local variables when we do the include below
    extract($this->_data);
    
    $path = "{$GLOBALS['temovico']['website_root']}/app/views/{$this->_controller}/{$this->_page}.html.php";
    if (!is_file($path)) {
      // If we don't find the file at /views/:controller/:action.html.php then 
      // look in the /views/shared_templates/:action.html.php
      $path = "{$GLOBALS['temovico']['website_root']}/app/views/shared_templates/{$this->_page}.html.php";
      if (!is_file($path)) {
        Logger::error("Template not found: $path", array("view"));
        throw new TemplateNotFoundException($path);
      }
    }
    include($path);
  }

  /**
   * Renders a template without wrapping it in a layout. 
   *
   * @param string $partial The partial we're rendering
   * @param string $local_vars An associative array of variables to make available to the partial
   * @param bool $render_as_string Returns the rendered page as a string if TRUE
   * @return mixed A string of the resulting html if $render_as_string is TRUE, otherwise (by default) void
   */
  public function render_partial($partial, $local_vars = null, $render_as_string = FALSE) {
      
    // I would prefer not using this muddy scoping and to require that local_vars
    // be used explicitly in partials so as to encourage readability.
    // for now we'll keep it
    if (is_array($local_vars)) {
      $this->_prior_data = $this->_data;
      $this->_data = array_merge($this->_data, $local_vars);
    }
	
  	// turn /wtf/show into /wtf/_show etc.
  	$partial = preg_replace('%/([^/])$%', '/_$1', $partial); 
	
  	// render as a partial (i.e no layout)
  	$rendered = $this->render($partial, $layout = null, $render_as_string);
	
	  // restore the data that existed before the merge with $local_vars
    if (is_array($local_vars)) {
      $this->_data = $this->_prior_data;
    }
    
    // this return value will usually be void and discarded because
    // $render_as_string will be FALSE. If it is TRUE, then this will
    // contain the rendered partial html
    return $rendered;
  }
    
  /**
   * Renders stylesheets, appending appropriate timestamps
   *
   * @return void
   **/
  private function render_stylesheets() {
    array_unshift($this->stylesheets, 'screen.css');
    foreach ($this->stylesheets as $stylesheet) {
       $href = self::get_static_filename("{$GLOBALS['temovico']['static_dirs']['stylesheets']}/{$stylesheet}");
      echo '<link href="' . $href . '" rel="stylesheet" type="text/css" />' . "\n";
    }    
  }
    
  /**
   * Renders javascripts, appending appropriate timestamps
   *
   * @return void
   **/
  private function render_javascripts() {
    array_unshift($this->javascripts, 'base.js');
    foreach ($this->javascripts as $javascript) {
      $src = self::get_static_filename("{$GLOBALS['temovico']['static_dirs']['stylesheets']}/{$javascript}");
      echo '<script src="' . $src . '" type="text/javascript"></script>' . "\n";
    }    
  }

  /**
   * Renders the errors assocaited with a given form field in a template
   * 
   * @param string $field The field to render errors for
   * @param bool $render_as_string Returns the rendered page as a string if TRUE
   * @return mixed A string of the resulting html if $render_as_string is TRUE, otherwise (by default) void 
   **/
  public function render_errors_for_field($field, $render_as_string = FALSE) {
    $html = '';
    if (!$this->errors_exist_for_field($field)) {
      return $html;
    }
    $errors = $this->_data['errors'];
    
    if (array_key_exists($field, $errors) && (sizeof($errors[$field]) > 1)) {
      $html = "<ul class=\"errors\">\n";
      foreach ($errors[$field] as $error) {
        $html .= "\t<li>$error</li>\n";
      }
      $html .= "</ul>\n";
    } elseif (array_key_exists($field, $errors) && (sizeof($errors[$field]) == 1)) {
      $html = "<p class=\"error\">" . $errors[$field][0] . "</p>\n";
    }
    
    if ($render_as_string) {
      return $html;
    } else {
      echo $html;
    }
  }

  /**
   * Renders the errors assocaited with a given form field in a template
   * 
   * @param string $field The field to check errors for
   * @return boolean TRUE if errors exist for $field, FALSE otherwise
   **/
  public function errors_exist_for_field($field) {
    if (!array_key_exists('errors', $this->_data)) {
      return FALSE;
    }
    $errors = $this->_data['errors'];
    return is_array($errors) && $errors && array_key_exists($field, $errors);
  }
  
  /**
   * Renders a timestamped filename for static asset
   *
   * @param string $filename The filename to get a timestamped name for
   * @return string The timestamped static filename
   **/
   
  // TODO allow support a different static server
  public static function get_static_filename($filename) {
    // Don't modify absolute URLs
    if (strpos($filename, 'http://') === 0) {
      return $filename;
    }
    
    return $filename . '?' . filemtime($filename);
  }
}

?>
