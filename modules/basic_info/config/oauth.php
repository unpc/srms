<?php

$host = $_SERVER['HTTP_HOST'];

$config['consumers']['girl'] = [
    'title' => '实验室基本情况表',
    'key' => 'c7829852-afd6-484e-b938-ebcdba56b58c',
    'secret' => '068651a0-dcf1-473d-adef-4e4daf8f8226',
    'redirect_uri' => 'http://' . $host . "/girl/oauth/client/auth?source=girl.". LAB_ID,
    'auto_authorise' => TRUE,
];
