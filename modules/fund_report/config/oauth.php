<?php

$host = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];


$config['consumers']['fund_report.' . LAB_ID] = [
    'title' => '基金申报管理',
    'key' => '98a7cd5d-1652-4a6a-bf2a-24db7aa7e81a',
    'secret' => '27d81a2a-d34d-4q8e-b445-ddb3d9b2215e',
    'redirect_uri' => $host . "/fundreport/oauth/client/auth?source=fundreport.". LAB_ID,
    'auto_authorise' => TRUE,
];
