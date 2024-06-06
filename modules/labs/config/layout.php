<?php

$config['sidebar']['current_user'] = [
	'view'=>'labs:sidebar/current_user',
	'weight' => -50,
];
$config['sidebar']['signup'] = ['view'=>'labs:sidebar/signup', 'weight'=>-40];

$config['sidebar']['lab_signup'] = ['view'=>'labs:sidebar/lab_signup', 'weight'=>-30];

$config['sidebar.menu']['labs'] = [
	'desktop' => [
		'title' => '实验室模块',
		'icon' => '!labs/icons/48/labs.png',
		'url' => '!labs/index',
	],
	'icon' => [
		'title' => '实验室模块',
		'icon' => '!labs/icons/32/labs.png',
		'url' => '!labs/index',
	],
	'list'=>[
		'title' => '实验室模块',
		'icon' => '!labs/icons/16/labs.png',
		'url' => '!labs/index',
		'class'=>'icon-book'
	],
    'category' => "人员管理",
    'category_weight' => 100
];
