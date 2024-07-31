<?php

$host = $_SERVER['HTTP_HOST'];

$config['consumers']['summary.geneegroup'] = [
    'title'          => '大型仪器统计系统',
    'key'            => '6Y0BH4QE-9XQC-QBBT-MZ9F-CYYODWM4VUJL',
    'secret'         => '3GK357U3-Z3J0-QCZK-E6TG-Z0E2CPHCDEYV',
    'redirect_uri'   => 'http://' . $host . "/summary/oauth/client/auth?source=summary.geneegroup",
    'auto_authorise' => true,
];
