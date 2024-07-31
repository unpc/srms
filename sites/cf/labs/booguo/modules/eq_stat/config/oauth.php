<?php

$host = $_SERVER['HTTP_HOST'];

$config['consumers']['eqstat.geneegroup'] = [
    // 'disabled' => TRUE,
    'title' => '大型仪器统计系统',
    'key' => 'ACD508A2-9A09-47E6-B480-FC6D1388079E',
    'secret' => 'B0AD3635-4E69-4171-AB0D-869832DA1703',
    'redirect_uri' => 'http://' . $host . "/stat/oauth/client/auth?source=eqstat.geneegroup",
    'auto_authorise' => TRUE,
];
