<?php
/*

输出指定 lab 的 daemon (xiaopei.li@2013-03-11)

usage: SITE_ID=cf LAB_ID=test php get_daemon.php

*/

//防止错误输出
ini_set('display_errors', FALSE);

require dirname(__FILE__) . '/base.php';

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



echo '# daemons for SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID;
echo  "\n";

$daemons = Config::get('daemon');

$envs = [
	'Q_ROOT_PATH' => $root,
	'SITE_ID' => SITE_ID,
	'LAB_ID' => LAB_ID,
	];

$daemon_envs = "";
foreach ($envs as $env_key => $env_value) {
	// $daemon_envs .= "--env=\"$env_key=$env_value\" ";
	// TODO 由于加 " 后在 daemon 运行时可能出现重复 " 问题, 所以这里暂时不加 " 了
	$daemon_envs .= "-e $env_key=$env_value ";
}

foreach ((array)$daemons as $name => $opts) {
	// 期望 daemon 运行时的格式如下:
	// /usr/bin/daemon  --name="lims2_daemon_dispatcher" --env="SITE_ID=com" --env="LAB_ID=genee" --command="/home/xiaopei.li/lims2/cli/notification/dispatcher.php" --respawn

	echo "# " . $opts['title'] . "\n";

	echo $daemon_envs;

	$daemon_opts = [
		'name' => 'lims2_daemon_' . SITE_ID . '_' . LAB_ID . '_' . $name,
		'command' => strtr($opts['command'], [ROOT_PATH => $root]),
		// 'respawn' => $opts['respawn'],
		// respawn 选项可使 daemon client 进程被 kill 后重启,
		// 该项一般为期望设置, 故以防开发人员在 daemon 配置中忘加,
		// 强制设 TRUE
		'respawn' => TRUE,
		];
	foreach ($daemon_opts as $daemon_opt => $daemon_opt_value) {
		if (is_bool($daemon_opt_value) && $daemon_opt_value) {
			echo "--$daemon_opt ";
		}
		else {
			// echo "--$daemon_opt=\"$daemon_opt_value\" ";
			// TODO 由于加 " 后在 daemon 运行时可能出现重复 " 问题, 所以这里暂时不加 " 了
			echo "--$daemon_opt=$daemon_opt_value ";
		}
	}
	echo "\n";
}
