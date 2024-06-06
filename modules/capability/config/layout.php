<?php
$scheme = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$config['sidebar.menu']['capability'] = [
    'desktop' => [
        'title' => '绩效考核',
        'icon' => '!capability/icons/48/capability.png',
        'url' => $scheme . $host . '/capability/?oauth-sso=capability.' . LAB_ID,
        'target' => '_blank'
    ],
    'icon' => [
        'title' => '绩效考核',
        'icon' => '!capability/icons/32/capability.png',
        'url' => $scheme . $host . '/capability/?oauth-sso=capability.' . LAB_ID,
        'target' => '_blank'
    ],
    'list'=>[
        'title' => '绩效考核',
        'class'=>'icon-task',
        'url' => $scheme . $host . '/capability/?oauth-sso=capability.' . LAB_ID,
        'target' => '_blank'
    ],
];
