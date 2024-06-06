<?php

$host = $_SERVER['HTTP_HOST'].'/girl';

$config['sidebar.menu']['basic_info'] = [
    'desktop' => [
        'title' => '实验室信息统计',
        'icon' => '!basic_info/icons/48/basic_info.png',
        'url' => 'http://' . $host . '/?oauth-sso=girl.' . LAB_ID,
        'target' => '_blank',
    ],
    'icon' => [
        'title' => '实验室信息统计',
        'icon' => '!basic_info/icons/32/basic_info.png',
        'url' => 'http://' . $host . '/?oauth-sso=girl.' . LAB_ID,
        'target' => '_blank',
    ],
    'list'=>[
        'title' => '实验室信息统计',
        'class' => 'icon-trend_chart',
        'url' => 'http://' . $host . '/?oauth-sso=girl.' . LAB_ID,
        'target' => '_blank',
    ],
    'category' => "数据中台",
    'category_weight' => 40
];
