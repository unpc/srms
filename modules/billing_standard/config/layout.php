<?php
$scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?: ($_SERVER['HTTPS'] ? 'https' : 'http');
$host = $scheme . '://' . $_SERVER['HTTP_HOST'];

$config['sidebar.menu']['billing_standard'] = [
    'desktop' => [
        'title' => '报销管理',
        'icon' => '!billing_standard/icons/48/billing.png',
        'url' => $host . '/billing/?oauth-sso=billing.' . LAB_ID,
        'target' => '_blank',
    ],
    'icon' => [
        'title' => '报销管理',
        'icon' => '!billing_standard/icons/32/billing.png',
        'url' => $host . '/billing/?oauth-sso=billing.' . LAB_ID,
        'target' => '_blank',
    ],
    'list'=>[
        'title' => '报销管理',
        'class'=>'icon-settlement',
        'url' => $host . '/billing/?oauth-sso=billing.' . LAB_ID,
        'target' => '_blank',
    ],
    'category' => "财务管理",
    'category_weight' => 80
];
