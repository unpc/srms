<?php
if (!$GLOBALS['preload']['gateway.perm_in_uno']) {
$config['user'] = [
	'name' => '人员管理',
	'items' => [
		'login' => [
			'subname' => '登录安全提示',
			'subitems' => [
				'single_login' => '同账号同一时间单一登录',
			],
		]
	]
];
}