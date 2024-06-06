<?php

$config['servers']['yiqikong'] = [
    'url' => 'http://www.17kong.com/api',
    'signup' => 'http://www.17kong.com/Join/Signup',
    'client_id' => '0d4b6a53-1237-4d5b-b6d3-8b730aa5cc3f',
    'client_secret' => '18b33047-ad1d-4b2a-99b2-5a6169dd2d7b'
];

$config['servers']['jarvis'] = [
    'client_id' => '17e2a091-f463-4227-b1a1-5eecf0429d1e',
    'client_secret' => '33dcc8c6-27e1-44a8-b011-3d15fc3bedb2'
];

// jarvis
$config['identity']['17e2a091-f463-4227-b1a1-5eecf0429d1e'] = [
    'secret' => '33dcc8c6-27e1-44a8-b011-3d15fc3bedb2',
    'scope' => ['*']
];

$config['identity']['0d4b6a53-1237-4d5b-b6d3-8b730aa5cc3f'] = [
    'secret' => '18b33047-ad1d-4b2a-99b2-5a6169dd2d7b',
    'scope' => ['*']
];
