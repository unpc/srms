<?php
$scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?: ($_SERVER['HTTPS'] ? 'https' : 'http');
$host = $scheme . '://' . $_SERVER['HTTP_HOST'];

$config['sidebar.menu']['summary'] = [
    'desktop' => [
        'title'  => '大数据体系',
        'icon'   => '!summary/icons/48/summary.png',
        'url'    => $host . '/summary/?oauth-sso=summary.' . LAB_ID,
        'target' => '_blank',
    ],
    'icon'    => [
        'title'  => '大数据体系',
        'icon'   => '!summary/icons/32/summary.png',
        'url'    => $host . '/summary/?oauth-sso=summary.' . LAB_ID,
        'target' => '_blank',
    ],
    'list'    => [
        'title'  => '大数据体系',
        'class'  => 'icon-data_analysis',
        'url'    => $host . '/summary/?oauth-sso=summary.' . LAB_ID,
        'target' => '_blank',
    ],
    'category' => "数据中台",
    'category_weight' => 40
];
