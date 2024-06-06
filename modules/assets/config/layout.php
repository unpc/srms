<?php

$host = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

$config['sidebar.menu']['assets'] = [
    'desktop' => [
        'title' => '资产同步管理',
        'icon' => '!equipments/icons/48/equipments.png',
        'url' => $host . '/gateway/?oauth-sso=gateway.' . LAB_ID,
        'target' => '_blank'
    ],
    'icon' => [
        'title' => '资产同步管理',
        'icon' => '!equipments/icons/32/equipments.png',
        'url' => $host . '/gateway/?oauth-sso=gateway.' . LAB_ID,
        'target' => '_blank'
    ],
    'list'=>[
        'title' => '资产同步管理',
        'icon' => '!equipments/icons/16/equipments.png',
        'url' => $host . '/gateway/?oauth-sso=gateway.' . LAB_ID,
        'target' => '_blank'
    ],
];

