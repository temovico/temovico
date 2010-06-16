<?
// Exceptions

class BRBException extends Exception { }

class TemplateNotFoundException extends Exception {
    
   public function __construct($template) {
       parent::__construct("Template not found: {$template}");
   }
   
}

class FileNotFoundException extends Exception {
    
   public function __construct($url) {
       parent::__construct("File not found: {$url}");
   }
   
}

?>