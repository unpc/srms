#!/usr/bin/env php
<?php

ini_set('include_path', '/etc/php5/cgi/php.ini');
require "base.php";

Upgrader::echo_separator();
Upgrader::echo_title('系统检测信息如下：');


$upload_size = ini_get('upload_max_filesize');
$post_size = ini_get('post_max_size');

$exist_suhosin = extension_loaded('sohosin');

Upgrader::echo_success('当前系统中upload_max_filesize值为:'.$upload_size);
Upgrader::echo_success('当前系统中post_max_size值为:'.$post_size);

Upgrader::echo_separator();
if ($exist_suhosin) {
	Upgrader::echo_success('当前系统存在suhosin插件');
	$whitelist = ini_get('suhosin.executor.include.whitelist');
	Upgrader::echo_success($whitelist);
}
else {
	Upgrader::echo_fail('当前系统不存在suhosin插件');
}
Upgrader::echo_separator();

