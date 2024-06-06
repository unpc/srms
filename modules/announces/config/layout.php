<?php

$config['sidebar.menu']['announces'] = [
	'desktop' => [
		'title' => '系统公告',
		'icon' => '!announces/icons/48/announces.png',
		'url' => '!announces',
		'notif_callback' => 'Announce::notif_callback',
	],
	'icon' => [
		'title' => '系统公告',
		'icon' => '!announces/icons/32/announces.png',
		'url' => '!announces',
		'notif_callback' => 'Announce::notif_callback',
	],
	'list'=>[
		'title' => '系统公告',
		'url' => '!announces',
		'notif_callback' => 'Announce::notif_callback',
		'class' => 'icon-voice1',
	],
    'category' => "辅助管理",
    'category_weight' => 60
];
