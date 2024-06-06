<?php

$scheme = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
$host = $scheme . $_SERVER['HTTP_HOST'];

$config['sidebar.menu']['eq_stat'] = [
    'desktop' => [
        'title' => '仪器统计',
        'icon'  => '!eq_stat/icons/48/eq_stat.png',
        'url' => $host . '/stat/?oauth-sso=eqstat.' . LAB_ID,
        'target' => '_blank',
    ],
    'icon'    => [
        'title' => '仪器统计',
        'icon'  => '!eq_stat/icons/32/eq_stat.png',
        'url' => $host . '/stat/?oauth-sso=eqstat.' . LAB_ID,
        'target' => '_blank',
    ],
    'list'    => [
        'title' => '仪器统计',
        'class' => 'icon-trend_chart',
        'url' => $host . '/stat/?oauth-sso=eqstat.' . LAB_ID,
        'target' => '_blank',
    ],
    'category' => "数据中台",
    'category_weight' => 40
];
