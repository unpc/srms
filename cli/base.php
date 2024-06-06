<?php

$root_path = isset($_SERVER['Q_ROOT_PATH']) ? $_SERVER['Q_ROOT_PATH'] : dirname(__FILE__).'/..';

$root_path = preg_replace('/\/+$/', '', $root_path);
if(function_exists('realpath') && @realpath($root_path) !== FALSE){
	define('ROOT_PATH',  @realpath($root_path).'/');
}else{
	define('ROOT_PATH', $root_path.'/');
}

if ( file_exists(ROOT_PATH. 'public/globals.php') ) { 
	require ROOT_PATH. 'public/globals.php'; 
}

$phar_path = ROOT_PATH.'system.phar';
if (is_file($phar_path)) {
	define('SYS_PATH', 'phar://'.ROOT_PATH.'system.phar/');
}
else {
	define('SYS_PATH', ROOT_PATH.'system/');
}

require SYS_PATH.'core/cli.php';
