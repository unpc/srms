<?php

$config['sidebar.menu']['eq_ban'] = [
	'desktop' => [
		'title' => '黑名单',
		'icon' => '!equipments/icons/48/eq_ban.png',
		'url' => '!eq_ban'
	],
	'icon' => [
		'title' => '黑名单',
		'icon' => '!equipments/icons/32/eq_ban.png',
		'url' => '!eq_ban'
	],
	'list'=>[
		'title' => '黑名单',
		'icon' => '!equipments/icons/16/eq_ban.png',
		'url' => '!eq_ban',
		'class'=>'icon-member_edit'
	],
];

// 当有信用管理下的黑名单模块时
if (isset($config['sidebar.menu']['credit_ban'])) {
    unset($config['sidebar.menu']['eq_ban']);
}
