<?php

$config['sidebar.menu']['iot_gdoor'] = [
    'desktop' => [
        'title' => '门牌管理',
        'icon' => '!iot_gdoor/icons/48/iot_gdoor.png',
        'url' => 'http://uno.test.gapper.in/uno/#/admin/app-entry?entry=@entry:gdoor/doors',
        'target' => '_blank'
    ],
    'icon' => [
        'title' => '门牌管理',
        'icon' => '!iot_gdoor/icons/32/iot_gdoor.png',
        'url' => 'http://uno.test.gapper.in/uno/#/admin/app-entry?entry=@entry:gdoor/doors',
        'target' => '_blank'
    ],
    'list'=>[
        'title' => '门牌管理',
        'icon' => '!iot_gdoor/icons/16/iot_gdoor.png',
        'url' => 'http://uno.test.gapper.in/uno/#/admin/app-entry?entry=@entry:gdoor/doors',
        'target' => '_blank',
        'class'=>'icon-door'
	],
    'category' => "辅助管理",
    'category_weight' => 60
];
