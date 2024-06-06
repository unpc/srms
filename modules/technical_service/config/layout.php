<?php

$config['sidebar.menu']['technical_service'] = [
	'desktop' => [
		'title' => '技术服务',
		'icon' => '!technical_service/icons/48/technical_service.png',
		'url' => '!technical_service/list/index',
	],
	'icon' => [
		'title' => '技术服务',
		'icon' => '!technical_service/icons/32/people.png',
		'url' => '!technical_service/list/index',
	],
	'list'=>[
		'title' => '技术服务',
		'class'=>'icon-member',
		'url' => '!technical_service/list/index',
		
	],
    'category' => "资源管理",
    'category_weight' => 90
];

$config['sidebar.menu']['technical_service_record'] = [
	'desktop' => [
		'title' => '项目检测',
		'icon' => '!technical_service/icons/48/technical_service.png',
		'url' => '!technical_service/record/index',
	],
	'icon' => [
		'title' => '项目检测',
		'icon' => '!technical_service/icons/32/people.png',
		'url' => '!technical_service/record/index',
	],
	'list'=>[
		'title' => '项目检测',
		'class'=>'icon-member',
		'url' => '!technical_service/record/index',
		'key'   => ['!technical_service/record']
	],
    '#module' => 'technical_service',
	'category' => "资源管理",
    'category_weight' => 90
];