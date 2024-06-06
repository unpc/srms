#!/usr/bin/env php
<?php
require 'base.php';

// 获得所有或指定的 预加载(preload)和配置(config), 以 json 输出
// 支持 无参数/1个参数/多个参数
// (xiaopei.li@2012-08-01)

$info = Application::info();

if ($argc > 1) {

	$k_v_pairs = [];

	array_shift($argv);

	foreach ($argv as $key) {
		$key_parts = explode(':', $key, 2);

		$type = $key_parts[0];
		if ('config' == $type) {
			$foo = explode('.', $key_parts[1], 2);
			$file = $foo[0];
			$config = $foo[1];

			$k_v_pairs[$key] = $info['config'][$file][$config];
		}
		else if ('preload' == $type) {
			$config = $key_parts[1];

			$k_v_pairs[$key] = $info['preload'][$config];
		}

	}

	$output = $k_v_pairs;
}
else {
	$output = $info;
}

echo json_encode($output);
