<?php

$host = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'].'/approval';

$config['sidebar.menu']['sample_approval'] = [
    'desktop' => [
        'title' => '测样报告审核',
        'icon' => '!sample_approval/icons/64/sample_approval.png',
        'url' => $host . '/?oauth-sso=approval',
        'target' => '_blank'
    ],
    'icon' => [
        'title' => '测样报告审核',
        'icon' => '!sample_approval/icons/32/sample_approval.png',
        'url' => $host . '/?oauth-sso=approval',
        'target' => '_blank'
    ],
    'list'=>[
        'title' => '测样报告审核',
        'icon' => '!sample_approval/icons/16/sample_approval.png',
        'url' => $host . '/?oauth-sso=approval',
        'target' => '_blank'
    ],
    'category' => "资源管理",
    'category_weight' => 90
];
