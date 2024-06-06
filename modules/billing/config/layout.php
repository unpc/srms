<?php

// $config['sidebar']['billing_lab'] = ['view'=>'billing:lab/brief','weight'=>'-20'];

$config['sidebar.menu']['billing'] = [
	'desktop' => [
		'title' => '财务中心',
		'icon' => '!billing/icons/48/billing.png',
		'url' => '!billing',
	],
	'icon' => [
		'title' => '财务中心',
		'icon' => '!billing/icons/32/billing.png',
		'url' => '!billing',
	],
	'list'=>[
		'title' => '财务中心',
		'class'=>'icon-settlement',
		'url' => '!billing',
	],
    'category' => "财务管理",
    'category_weight' => 80
];
