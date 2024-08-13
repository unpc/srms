<?php

$host = $_SERVER['HTTP_HOST'] . '/billing';

$config['consumers']['billing.neu'] = [
    'title' => '东北大学报销管理系统',
    'key' => '51d1d072-a596-4938-a1bc-d5d118001da8',
    'secret' => 'fa9960ac-1f73-46ad-b3a4-be9fc3705966',
    'redirect_uri' => 'http://' . $host . "/oauth/client/auth?source=billing.neu",
    'auto_authorise' => TRUE,
];
