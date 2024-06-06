<?php
require __DIR__ . '/../libraries/engine.php';
require __DIR__ . '/../libraries/exception.php';

$config['camunda'] = [
    'driver' => 'Camunda',
    'options' => [
        'engine' => 'default',
        'api_root' => 'http://acms.zcmu.edu.cn/bpm/api',
        'username' => 'genee',
        'password' => '83719730',
    ]
];
