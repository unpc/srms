#!/usr/bin/env php
<?php
// 遍历 site/lab 对所有 lab dpkg 打包 by xiaopei.li
echo "\033[32m该方法会对所有的labs进行encode操作(将\$GLOBAS配置写入代码)，这些包需要在服务器上独立运行!!\033[0m\n";
if ($argc < 2) {
	echo "usage: php debian_package_all.php ../\n";
	exit;
}

if (is_dir($argv[1])) {
	define('ROOT_PATH', realpath($argv[1]).'/');
}
else {
	define('ROOT_PATH', dirname(__FILE__).'/');
}

$labs = glob(ROOT_PATH.'sites/*/labs/*');


echo "==> 删除旧的 *.phar\n\n";
exec("find ../ -name '*.phar' -delete");

foreach ($labs as $lab) {

	if (!preg_match('|sites/([^/]+)/labs/([^/]+)|', $lab, $matches)) continue;

	$site_id = $matches[1];
	$lab_id = $matches[2];

    echo "==> 针对 " . $site_id . "\t" . $lab_id . " 做特殊打包\n";
    exec("php debian_package.php -s $site_id -l $lab_id -e");
    echo "==> 针对 " . $site_id . "\t" . $lab_id . " 打包 OK\n";

    echo "==> 删除此次打包生成的 *.phar\n";
    exec("find ../ -name '*.phar' -delete");
    echo "==> 清理 OK\n\n";
}

