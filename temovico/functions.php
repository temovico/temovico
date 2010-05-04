<?php

/**
 * Split the url path on '/' and return and array of tokens
 *
 * @param string $path The path to split on '/'
 * @return array
 */
function split_path($path = null) {
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

function build_query_string($params) {
	$result = '?';
	foreach($params as $key => $val) {
		if (strlen($result) > 1) {
			$result .= '&amp;';
		}
		$result .= $key;
		if (isset($val) && strlen ($val) > 0) {
			$result .= '=' . urlencode($val);
		}
	}
	return $result;
}

function str_starts_with($haystack, $needle){
  return strpos($haystack, $needle) === 0;
}

function squeeze($str) {
  $str = preg_replace('/[\r\t\n]/', ' ', $str);
  $str = preg_replace ('/ +/', ' ', $str);
  return $str;
}

?>