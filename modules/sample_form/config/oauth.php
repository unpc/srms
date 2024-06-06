<?php

$host = $_SERVER['HTTP_HOST'] . '/sample';

$config['consumers']['sample_form'] = [
    'title' => '样品检测',
    'key' => '8ec0ee65-61ff-4f97-9fa2-8500ba4a7f1e',
    'secret' => '113de56a-948e-4ac7-a202-85f4bc0b5f28',
    'redirect_uri' => 'http://' . $host . '/oauth/client/auth?source=sample.'. LAB_ID,
    'auto_authorise' => TRUE,
];
