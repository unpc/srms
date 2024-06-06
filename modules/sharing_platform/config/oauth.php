<?php

$host = $_SERVER['HTTP_HOST'];

$config['consumers']['sharing_platform'] = [
    'title' => '共享平台',
    'key' => '98b7cd5d-1632-4a6a-bf2a-24db7aa7e81a',
    'secret' => '27d81ada-a34d-4q8e-b445-ddb3d9b2215e',
    'redirect_uri' => 'http://' . $host . "/platform/oauth/client/auth?source=sharingplatform.". LAB_ID,
    'auto_authorise' => TRUE,
];