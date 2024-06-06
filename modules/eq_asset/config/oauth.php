<?php

$host = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

$config['consumers']['eqasset'] = [
    'title' => '仪器资产信息表',
    'key' => '51d1d072-a596-4938-a1bc-d5d118001da8',
    'secret' => 'fa9960ac-1f73-46ad-b3a4-be9fc3705966',
    'redirect_uri' => $host . "/eqasset/oauth/client/auth?source=eqasset.". LAB_ID,
    'auto_authorise' => TRUE,
];
