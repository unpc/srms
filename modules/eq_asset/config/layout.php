<?php

$host = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

$config['sidebar.menu']['eq_asset'] = [
    'desktop' => [
        'title' => '仪器资产信息表',
        'icon' => '!eq_asset/icons/48/eq_asset.png',
        'url' => $host . '/eqasset/?oauth-sso=eqasset.' . LAB_ID,
        'target' => '_blank',
    ],
    'icon' => [
        'title' => '仪器资产信息表',
        'icon' => '!eq_asset/icons/32/eq_asset.png',
        'url' => $host . '/eqasset/?oauth-sso=eqasset.' . LAB_ID,
        'target' => '_blank',
    ],
    'list'=>[
        'title' => '仪器资产信息表',
        'class'=>'icon-detail-list',
        'icon' => '!eq_asset/icons/16/eq_asset.png',
        'url' => $host . '/eqasset/?oauth-sso=eqasset.' . LAB_ID,
        'target' => '_blank',
    ],
    'category' => "资源管理",
    'category_weight' => 90
];

