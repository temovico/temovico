<?php

////////////////////////////
///  DEFINE ROUTES HERE  ///
////////////////////////////
$GLOBALS['temovico']['routes'] = array(
    #'path/for/route' => array($controller, $action, $getAndPostParams)
    
    # HOME
    
    'users/create'          => array('Home', 'create', array('user')),
    'users/'                => array('Home', 'users'),
    'user/:username/delete' => array('Home', 'delete'),
    'user/:username'        => array('Home', 'user'),
    ''                      => array('Home', 'index')
    
);

?>