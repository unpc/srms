<?php

$host = $_SERVER['HTTP_HOST'];

$config['sidebar.menu']['eq_stat'] = [
    'desktop' => [
        'title' => '仪器统计',
        'icon' => '!eq_stat/icons/48/eq_stat.png',
        'url' => 'http://' . $host . '/stat/?oauth-sso=eqstat.geneegroup',
        'target' => '_blank'
    ],
    'icon' => [
        'title' => '仪器统计',
        'icon' => '!eq_stat/icons/32/eq_stat.png',
        'url' => 'http://' . $host . '/stat/?oauth-sso=eqstat.geneegroup',
        'target' => '_blank'
    ],
    'list'=>[
        'title' => '仪器统计',
        'icon' => '!eq_stat/icons/16/eq_stat.png',
        'url' => 'http://' . $host . '/stat/?oauth-sso=eqstat.geneegroup',
        'target' => '_blank'
    ],
];
