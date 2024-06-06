<?php
/*

给出一个路径, 扫描该路径下所有 lab, 并遍历对其调用 get_daemon.php (xiaopei.li@2013-03-12)

usage: php get_all_daemon.php /usr/share/lims2

*/

if ($argc < 2) {
	echo "遍历生成 daemon\n";
	echo "命令格式: php get_all_daemon.php /usr/share/lims2\n";
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


$shortopts = "r::";
$longopts = [
	'root::',
	];

$opts = getopt($shortopts, $longopts);
if (isset($opts['r']) || isset($opts['root'])) {
	$root = $opts['r'] ? : $opts['root'];
}
else {
	$root = ROOT_PATH;
}


/// run
$labs = glob(ROOT_PATH.'sites/*/labs/*');

$daemons = [];

$get_daemon = ROOT_PATH . 'cli/get_daemon.php';
$get_daemon_opts = '';
$get_daemon_opts .= $root ? " -r=$root" : '';



foreach ($labs as $lab) {

	if (!preg_match('|sites/([^/]+)/labs/([^/]+)|', $lab, $matches)) continue;

	$site_id = $matches[1];
	$lab_id = $matches[2];
	$cmd = strtr('SITE_ID=%site_id LAB_ID=%lab_id %script %opts 2>/dev/null %then', [
					 '%site_id' => $site_id,
					 '%lab_id' => $lab_id,
					 '%script' => "php $get_daemon",
					 '%opts' => $get_daemon_opts,
					 '%then' => " | grep '^[0-9#\-]'",
					 ]);

	//	echo $cmd;
	//	echo "\n";
	//	continue;

	ob_start();
	// passthru("SITE_ID=$site_id LAB_ID=$lab_id php $get_daemon 2>/dev/null", $ret);
	passthru($cmd, $ret); // 用 grep 去除非 daemon 或 注释 的行
	$content_grabbed = '';
	if ($ret == 0) {
		$content_grabbed = ob_get_contents();
	}
	ob_end_clean();

	// exec() returns **the last line from the result of the command**
	// If you need to execute a command and have all the data from the command passed directly back without any interference, use the passthru() function.

	echo $content_grabbed;
	// $daemons[$site_id . '_' . $lab_id] = $content_grabbed;
}


// echo join($daemons, "\n");
