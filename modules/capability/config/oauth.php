<?php
//马坤 2024.3.26 修改 绩效考核兼容https
$scheme = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$config['consumers']['capability.' . LAB_ID] = [
    // 'disabled' => TRUE,
    'title' => '大型仪器绩效考核系统',
    'key' => 'capability',
    'secret' => '27d81a2a-d34d-4q8e-b445-ddb3d9b2215e',
    'redirect_uri' => $scheme . $host . "/capability/oauth/client/auth?source=capability." . LAB_ID,
    'auto_authorise' => TRUE,
];
