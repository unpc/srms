<?php
/*
输出指定 lab 的 sphinx 配置

usage: SITE_ID=cf LAB_ID=test php get_sphinx.php
*/

//停止报错, 防止错误输出
ini_set('display_errors', FALSE);

require dirname(__FILE__) . '/base.php';

echo '# lims2 sphinx conf for SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . "\n";

$sphinx_confs = Config::get('sphinx');

if ($sphinx_confs) foreach ($sphinx_confs as $conf_key => $conf_content) {

	if ($conf_content) {
		generate_sphinx_conf($conf_key, $conf_content);
	}
}

echo "\n# -- EOF --\n";


function generate_sphinx_conf($conf_key, $conf_content) {

	echo "# " . $conf_key . "\n";

	echo strtr(strtr( "index %site_id_%lab_id_%index: rt_default\n", [
					'%site_id' => SITE_ID,
					'%lab_id' => LAB_ID,
					'%index' => $conf_key,
					]), '-', '_');

	echo "{\n";

	echo strtr( "path = /var/lib/sphinxsearch/data/lims2/%site_id_%lab_id_%index\n", [
					'%site_id' => SITE_ID,
					'%lab_id' => LAB_ID,
					'%index' => $conf_key,
					]);

	foreach ((array)$conf_content['options'] as $key => $opts) {
		echo strtr( "%key = %value\n", [
						'%key' => $key,
						'%value' => $opts['value'],
						]);
	}

	foreach ((array)$conf_content['fields'] as $field => $opts) {
		echo strtr( "%type = %field\n", [
						'%type' => $opts['type'],
						'%field' => $field,
						]);
	}

	echo "}\n";
}
