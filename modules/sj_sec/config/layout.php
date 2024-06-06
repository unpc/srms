<?php
$host = $_SERVER[HTTP_HOST] . '/sj_sec';

$config['sidebar.menu']['sj_sec'] = [
    'desktop' => [
        'title' => '设备增减变动表',
        'icon' => '!eq_stat/icons/48/eq_stat.png',
        'url' => $host . '/?oauth-sso=',
        'target' => '_blank',
        ],
    'icon' => [
        'title' => '设备增减变动表',
        'icon' => '!eq_stat/icons/32/eq_stat.png',
        'url' => $host . '/?oauth-sso=',
        'target' => '_blank',
        ],
    'list'=>[
        'title' => '设备增减变动表',
        'icon' => '!eq_stat/icons/16/eq_stat.png',
        'url' => $host . '/?oauth-sso=',
        'target' => '_blank',
        ],
    ];
