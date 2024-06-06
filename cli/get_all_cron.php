<?php
/*
给出一个目录, 扫描该目录下所有 lab, 并遍历对其调用 get_cron.php(xiaopei.li@2012-06-29)

usage: php get_all_cron.php /usr/share/lims2
*/

if ($argc < 2) {
	echo "遍历生成 crontab\n";
	echo "命令格式: php get_all_cron.php [-u|--user[=www-data]] /usr/share/lims2\n";
	exit;
}


// handle opts
/// path
$path = $argv[$argc - 1];
if (is_dir($path)) {
	define('ROOT_PATH', realpath($path).'/');
}
else {
	define('ROOT_PATH', dirname(__FILE__).'/');
}

/// other opts
$shortopts = "u:r::p::";
$longopts = [
    'user:root::prefix::',
];


$opts = getopt($shortopts, $longopts);

if (isset($opts['u']) || isset($opts['user'])) {
	$user = $opts['u'] ? : $opts['user'];
}
else {
	die("usage: SITE_ID=cf LAB_ID=test php get_cron.php -u|--user=www-data\n");
}

if (isset($opts['r']) || isset($opts['root'])) {
	$root = $opts['r'] ? : $opts['root'];
}
else {
	$root = ROOT_PATH;
}

if (isset($opts['p']) || isset($opts['prefix'])) {
    $prefix = $opts['p'] ? : $opts['prefix'];
}
else {
    $prefix = '%s';
}

/// run
$labs = glob(ROOT_PATH.'sites/*/labs/*');

$crontabs = [];

$get_cron = ROOT_PATH . 'cli/get_cron.php';

$get_cron_opts = '';
$get_cron_opts .= $user ? " -u=$user" : '';
$get_cron_opts .= $root ? " -r=$root" : '';

$get_cron_opts .= $prefix ? " -p='$prefix'" : '';

foreach ($labs as $lab) {

	if (!preg_match('|sites/([^/]+)/labs/([^/]+)|', $lab, $matches)) continue;

	$site_id = $matches[1];
	$lab_id = $matches[2];
	$cmd = strtr('SITE_ID=%site_id LAB_ID=%lab_id %script %opts 2>/dev/null %then', [
					 '%site_id' => $site_id,
					 '%lab_id' => $lab_id,
					 '%script' => "php $get_cron",
					 '%opts' => $get_cron_opts,
					 '%then' => " | grep '^[0-9*@#]'",
					 ]);

	//	echo $cmd;
	//	echo "\n";
	//	continue;

    ob_start();
	// passthru("SITE_ID=$site_id LAB_ID=$lab_id php $get_cron 2>/dev/null", $ret);
	passthru($cmd, $ret); // 用 grep 去除非 cron 或 注释 的行
	$content_grabbed = '';
	if ($ret == 0) {
		$content_grabbed = ob_get_contents();
	}
	ob_end_clean();

	// exec() returns **the last line from the result of the command**
	// If you need to execute a command and have all the data from the command passed directly back without any interference, use the passthru() function.

	echo $content_grabbed;
	// $crontabs[$site_id . '_' . $lab_id] = $content_grabbed;
}


// echo join($crontabs, "\n");
