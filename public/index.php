<?php
// 	die('正在维护, 请稍后再来<br/>客服电话: 400-017-5664');

//系统维护代码
/*
$maintain_view = 'maintain/maintenance.phtml';
$path = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] . $path;

$_vars = array(
	'maintan_end_date' =>  date('Y.m.d H:i', strtotime('2014-1-1 10:11')),
	'path' => 'http://' .$_SERVER['HTTP_HOST'] . $path,
 );
if ( file_exists($maintain_view) ) {
	ob_start();
	extract($_vars);

	@include($maintain_view);

	$output = ob_get_contents();
	ob_end_clean();
	echo $output;
}

die;
*/
$max_age = 5;
if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) + $max_age > time()){
	header('Status: 304 NOT Modified');
	exit;
}

$dir = dirname(__FILE__).'/..';
if(function_exists('realpath') && @realpath($dir) !== FALSE){
	define('ROOT_PATH',  @realpath($dir).'/');
}else{exit(0);
	define('ROOT_PATH', $dir.'/');
}

if (file_exists(ROOT_PATH. 'public/globals.php')) {
    require ROOT_PATH. 'public/globals.php';
}

$phar_path = ROOT_PATH.'system.phar';
if (is_file($phar_path)) {
	define('SYS_PATH', 'phar://'.ROOT_PATH.'system.phar/');
}
else {
	define('SYS_PATH', ROOT_PATH.'system/');
}

$GLOBALS['SCRIPT_START_AT'] = microtime(TRUE);
require SYS_PATH.'core/bootstrap.php';
