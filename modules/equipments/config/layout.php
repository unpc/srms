<?php

$config['sidebar.menu']['equipments'] = [
	'desktop' => [
		'title' => '仪器管理',
		'icon' => '!equipments/icons/48/equipments.png',
		'url' => '!equipments/index',
	],
	'icon' => [
		'title' => '仪器管理',
		'icon' => '!equipments/icons/32/equipments.png',
		'url' => '!equipments/index',
	],
	'list'=>[
		'title' => '仪器管理',
		'class'=>'icon-equipment',
		'url' => '!equipments/index',
	],
    'category' => "资源管理",
    'category_weight' => 90
];

// $config['sidebar']['eq_feedback'] = 'equipments:feedback_notif';


//$config['sidebar.menu']['equipments_records'] = [
//    'desktop' => [
//        'title' => '仪器的使用记录',
//        'icon' => '!equipments/icons/48/equipments.png',
//        'url' => '!equipments/records/index',
//    ],
//    'icon' => [
//        'title' => '仪器的使用记录',
//        'icon' => '!equipments/icons/32/equipments.png',
//        'url' => '!equipments/index',
//    ],
//    'list'=>[
//        'title' => '仪器的使用记录',
//        'class'=>'icon-equipment',
//        'url' => '!equipments/records/index',
//    ],
//    '#module' => 'equipments',
//    '#accessible' => 'equipments',
//    'category' => "资源管理",
//];