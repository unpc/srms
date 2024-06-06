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


/// run
$labs = glob(ROOT_PATH.'sites/*/labs/*');

foreach ($labs as $lab) {

	if (!preg_match('|sites/([^/]+)/labs/([^/]+)|', $lab, $matches)) continue;

	$site_id = $matches[1];
	$lab_id = $matches[2];
	$cmd = strtr('SITE_ID=%site_id LAB_ID=%lab_id %script n', [
					 '%site_id' => $site_id,
					 '%lab_id' => $lab_id,
					 '%script' => "php is_valid_lab.php",
					 ]);

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
