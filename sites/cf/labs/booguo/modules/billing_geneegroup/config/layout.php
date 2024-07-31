<?php

$host = $_SERVER['HTTP_HOST'] . '/billing';

$config['sidebar.menu']['billing_geneegroup'] = [
    'desktop' => [
        'title' => '报销管理',
        'icon' => '!billing/icons/48/billing.png',
        'url' => 'http://' . $host . '/?oauth-sso=billing.geneegroup',
        'target' => '_blank'
    ],
    'icon' => [
        'title' => '报销管理',
        'icon' => '!billing/icons/32/billing.png',
        'url' => 'http://' . $host . '/?oauth-sso=billing.geneegroup',
        'target' => '_blank'
    ],
    'list'=>[
        'title' => '报销管理',
        'icon' => '!billing/icons/16/billing.png',
        'url' => 'http://' . $host . '/?oauth-sso=billing.geneegroup',
        'target' => '_blank'
    ],
];
