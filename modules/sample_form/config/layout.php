<?php
$host = $_SERVER['HTTP_HOST'] . '/sample';

$config['sidebar.menu']['sample_form'] = [
    'desktop' => [
        'title' => '样品检测',
        'icon' => '!billing/icons/48/billing.png',
        'url' => 'http://' . $host . '/?oauth-sso=sample.'. LAB_ID,
        'target' => '_blank'
    ],
    'icon' => [
        'title' => '样品检测',
        'icon' => '!billing/icons/32/billing.png',
        'url' => 'http://' . $host . '/?oauth-sso=sample.'. LAB_ID,
        'target' => '_blank'
    ],
    'list'=>[
        'title' => '样品检测',
        'icon' => '!billing/icons/16/billing.png',
        'url' => 'http://' . $host . '/?oauth-sso=sample.'. LAB_ID,
        'target' => '_blank'
    ],
];
