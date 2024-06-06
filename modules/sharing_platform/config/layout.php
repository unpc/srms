<?php

$host = $_SERVER['HTTP_HOST'].'/platform';

$config['sidebar.menu']['sharing_platform'] = [
    'desktop' => [
        'title' => '共享平台',
        'icon' => '!sharing_platform/icons/48/sharing_platform.png',
        'url' => 'http://' . $host . '/?oauth-sso=sharingplatform.' . LAB_ID,
        'target' => '_blank',
    ],
    'icon' => [
        'title' => '共享平台',
        'icon' => '!sharing_platform/icons/32/sharing_platform.png',
        'url' => 'http://' . $host . '/?oauth-sso=sharingplatform.' . LAB_ID,
        'target' => '_blank',
    ],
    'list'=>[
        'title' => '共享平台',
        'icon' => '!sharing_platform/icons/16/sharing_platform.png',
        'url' => 'http://' . $host . '/?oauth-sso=sharingplatform.' . LAB_ID,
        'target' => '_blank',
    ],
];
