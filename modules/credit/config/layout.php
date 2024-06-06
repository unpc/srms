<?php

// 黑名单转为内置模块
unset($config['sidebar.menu']['eq_ban']);
$config['sidebar.menu']['credit'] = [
    'desktop' => [
        'title' => '成员信用',
        'icon'  => '!credit/icons/48/credit.png',
        'url'   => '!credit/index',
    ],
    'icon'    => [
        'title' => '成员信用',
        'icon'  => '!credit/icons/32/credit.png',
        'url'   => '!credit/index',
    ],
    'list'    => [
        'title' => '成员信用',
        'class' => 'icon-credit02',
        'url'   => '!credit/index',
        'key'   => ['!credit/index']
    ],
    '#module' => 'credit',
    'category' => "信用管理",
    'category_weight' => 100
];
$config['sidebar.menu']['credit_record'] = [
    'desktop' => [
        'title' => '信用明细',
        'icon'  => '!credit/icons/48/credit.png',
        'url'   => '!credit/credit_record',
    ],
    'icon'    => [
        'title' => '信用明细',
        'icon'  => '!credit/icons/32/credit.png',
        'url'   => '!credit/credit_record',
    ],
    'list'    => [
        'title' => '信用明细',
        'class' => 'icon-credit02',
        'url'   => '!credit/credit_record',
        'key'   => ['!credit/credit_record']
    ],
    '#module' => 'credit',
    'category' => "信用管理",
    'category_weight' => 100
];
$config['sidebar.menu']['credit_ban'] = [
    'desktop' => [
        'title' => '黑名单',
        'icon'  => '!credit/icons/48/credit.png',
        'url'   => '!credit/ban',
    ],
    'icon'    => [
        'title' => '黑名单',
        'icon'  => '!credit/icons/32/credit.png',
        'url'   => '!credit/ban',
    ],
    'list'    => [
        'title' => '黑名单',
        'class' => 'icon-credit02',
        'url'   => '!credit/ban',
        'key'   => ['!credit/ban']
    ],
    '#module' => 'credit',
    'category' => "信用管理",
    'category_weight' => 100
];