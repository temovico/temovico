<?php

////////////////////////////
///  DEFINE ROUTES HERE  ///
////////////////////////////
$GLOBALS['temovico']['routes'] = array(
    #'path/for/route' => array($controller, $action, $getAndPostParams)
    
    # HOME
    ':username' => array('Home', 'index'),
    ''          => array('Home', 'index')
);

?>