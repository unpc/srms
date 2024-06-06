<?php

$scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?: ($_SERVER['HTTPS'] ? 'https' : 'http');
$host = $scheme . '://' . $_SERVER['HTTP_HOST'];

$config['consumers']['billing.' . LAB_ID] = [
    'title' => '报销管理',
    'key' => '51d1d072-a596-4938-a1bc-d5d118001da8',
    'secret' => 'fa9960ac-1f73-46ad-b3a4-be9fc3705966',
    'redirect_uri' => $host . "/billing/oauth/client/auth?source=billing.". LAB_ID,
    'auto_authorise' => TRUE,
];
