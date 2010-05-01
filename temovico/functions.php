<?php

/**
 * Split the url path on '/' and return and array of tokens
 *
 * @param string $path The path to split on '/'
 * @return array
 */
function splitPath($path = null) {
    if (!$path) {
        $path = $_SERVER['PATH_INFO'];
    }
    $splitPath = mb_split('/', $path);
    array_shift($splitPath);
    // Disregard trailing slash
    if (end($splitPath) == '') {
        array_pop($splitPath);
    }
    return $splitPath;
}

?>