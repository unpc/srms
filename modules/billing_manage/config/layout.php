<?php
$config['sidebar.menu']['billing_manage_fund'] = [
	'desktop' => [
		'title' => '经费管理',
		'icon' => '!billing_manage/icons/48/billing.png',
		'url' => '!billing_manage/index/fund',
	],
	'icon' => [
		'title' => '经费管理',
		'icon' => '!billing_manage/icons/48/billing.png',
		'url' => '!billing_manage/index/fund',
	],
	'list'=>[
		'title' => '经费管理',
		'class'=>'icon-settlement',
		'url' => '!billing_manage/index/fund',
        'key'   => ['!billing_manage/index/fund']
	],
    '#module' => 'billing_manage',
    'category' => "财务管理",
    'category_weight' => 80
];

$config['sidebar.menu']['billing_manage_transaction_fund'] = [
	'desktop' => [
		'title' => '财务明细',
		'icon' => '!billing_manage/icons/48/billing.png',
		'url' => '!billing_manage/index/transaction_fund',
	],
	'icon' => [
		'title' => '财务明细',
		'icon' => '!billing_manage/icons/48/billing.png',
		'url' => '!billing_manage/index/transaction_fund',
	],
	'list'=>[
		'title' => '财务明细',
		'class'=>'icon-settlement',
		'url' => '!billing_manage/index/transaction_fund',
        'key'   => ['!billing_manage/index/transaction_fund']
	],
    '#module' => 'billing_manage',
    'category' => "财务管理",
    'category_weight' => 80
];

$config['sidebar.menu']['billing_manage_stat_platform'] = [
	'desktop' => [
		'title' => '财务汇总',
		'icon' => '!billing_manage/icons/48/billing.png',
		'url' => '!billing_manage/index/stat_platform',
	],
	'icon' => [
		'title' => '财务汇总',
		'icon' => '!billing_manage/icons/48/billing.png',
		'url' => '!billing_manage/index/stat_platform',
	],
	'list'=>[
		'title' => '财务汇总',
		'class'=>'icon-settlement',
		'url' => '!billing_manage/index/stat_platform',
        'key'   => ['!billing_manage/index/stat_platform']
	],
    '#module' => 'billing_manage',
    'category' => "财务管理",
    'category_weight' => 80
];