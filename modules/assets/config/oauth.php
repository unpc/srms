<?php

$host = $_SERVER['HTTP_HOST'];

$config['consumers']['gateway'] = [
    'title' => '资产同步管理',
    'key' => 'ACD508A2-9A09-47E6-B480-FC6D1388079E',
    'secret' => '4DA56703-22F3-4E14-BDFA-49C4920F477F',
    'redirect_uri' => 'http://' . $host . "/gateway/oauth/client/auth?source=gateway." . LAB_ID,
    'auto_authorise' => TRUE,
];
