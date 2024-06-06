#!/usr/bin/env php
<?php
require 'base.php';
//定义颜色
define('ANSI_RED', "\033[31m");
define('ANSI_GREEN', "\033[32m");
define('ANSI_RESET', "\033[0m");
define('ANSI_HIGHLIGHT', "\033[1m");


$shortopts = 'c:m::o::';
$longopts = [
	'class:',
	'method::',
	'options::'
];

$opts = getopt($shortopts, $longopts);

if(count($opts)){
	$class = $opts['c'] ?: $opts['class'];
	$class = 'CLI_'.$class;
	$method = $opts['m'] ?: $opts['method'];
	$options = $opts['o'] ?: $opts['options'];
	$options = explode(',', $options);
}
elseif(count($argv) > 1){
	//去除脚本名称
	array_shift($argv);
    $ori_class = array_shift($argv);
    $class = 'CLI_'.$ori_class;
	$method = array_shift($argv);
	$options = $argv;
}
else{
	echo_normal("usage: cli [-c|--class][-m|--method][-o|--options]\n\t   <command> [<args>]\n\t   Ps: --options=args,args,...");
	echo_normal('');
	echo_normal('SITE_ID=xx LAB_ID=xx php cli.php class method arga argb argc');
	echo_normal('SITE_ID=xx LAB_ID=xx php cli.php -cclass -mmethod -oopta,optb,optc');
	echo_normal('SITE_ID=xx LAB_ID=xx php cli.php --class=class --method=method --options=arga,argb,argc');
	return;
}

//举例： equipment delete_expire_training
//$config['equipment::delete_expire_training'] = 'Nankai_CLI_Equipment::delete_expire_training';

$config_key = strtr('cli.%class::%method', [
    '%class'=> $ori_class,
    '%method'=> $method,
]);

if (Config::get($config_key)) list($class, $method) = explode('::', Config::get($config_key));

if (!class_exists($class)) {
	echo_fail("$class 不存在");
	return;
}

$methods = get_class_methods($class);
if(!$method || !method_exists($class, $method) || !in_array($method, $methods)){

	if(method_exists($class, '__index')){
		call_user_func([$class, '__index']);
	}
	elseif(count($methods)){
		echo_fail('可用的方法包括:');
		foreach ($methods as $method) {
			echo_normal($method);
		}
	}
	return;
}

if (Config::get('debug.time')) {
	$begin_time = time();
	Log::add(sprintf('Cli脚本: %s::%s 脚本于 [%s] 开始运行!', $class, $method, Date::format($begin_time)), 'clitime');
}
call_user_func_array([$class, $method], $options);
if (Config::get('debug.time')) {
	$end_time = time();
	Log::add(sprintf('Cli脚本: %s::%s 脚本于 [%s] 结束执行! 耗时 %s 秒!', $class, $method, Date::format($end_time), $end_time - $begin_time), 'clitime');
}

// fail 输出
function echo_fail($text='') {
	echo ANSI_RED;
	echo "$text\n";
	echo ANSI_RESET;
}

// success 输出
function echo_success($text='') {
	echo ANSI_GREEN;
	echo "$text\n";
	echo ANSI_RESET;
}

function echo_normal($test) {
	echo "$test\n";
}
