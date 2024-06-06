<?php
/*
给出一个目录, 扫描该目录下所有 lab, 并遍历对其调用 get_sphinx.php

usage: php get_all_sphinx.php /usr/share/lims2
*/

if ($argc < 2) {
	echo "遍历生成 sphinx conf\n";
	echo "命令格式: php get_all_sphinx.php /usr/share/lims2\n";
	exit;
}


// handle opts
/// path
$path = $argv[1];
if (is_dir($path)) {
	define('ROOT_PATH', realpath($path).'/');
}
else {
	define('ROOT_PATH', dirname(__FILE__).'/');
}

// die(ROOT_PATH . "\n");

/// run
$labs = glob(ROOT_PATH.'sites/*/labs/*');

$sphinx_confs = [];

$get_sphinx = ROOT_PATH . 'cli/get_sphinx.php';

foreach ($labs as $lab) {

	if (!preg_match('|sites/([^/]+)/labs/([^/]+)|', $lab, $matches)) continue;

	$site_id = $matches[1];
	$lab_id = $matches[2];
	$cmd = strtr('SITE_ID=%site_id LAB_ID=%lab_id %script 2>/dev/null %then', [
					 '%site_id' => $site_id,
					 '%lab_id' => $lab_id,
					 '%script' => "php $get_sphinx",
					 '%then' => " | grep -v '^Warning'",
					 // 当有目录但无 DB 时, 会产生警告, 如:
					 // Warning: mysqli::mysqli(): (42000/1049): Unknown database 'lims2_test' ...
					 // 所以 grep -v 消除之
					 ]);

	// echo $cmd;
	// echo "\n";
	//	continue;
	$content_grabbed = '';

	ob_start();
	// passthru("SITE_ID=$site_id LAB_ID=$lab_id php $get_sphinx 2>/dev/null", $ret);
	passthru($cmd, $ret); // 用 grep 去除非 sphinx 或 注释 的行
	if ($ret == 0) {
		$content_grabbed = ob_get_contents();
	}
	ob_end_clean();

	// exec() returns **the last line from the result of the command**
	// If you need to execute a command and have all the data from the command passed directly back without any interference, use the passthru() function.

	echo $content_grabbed;
	// $sphinxtabs[$site_id . '_' . $lab_id] = $content_grabbed;
}


// echo join($sphinxtabs, "\n");
