<?php

$config['application'] = [
    'name' => '南开大学大型仪器管理系统',
    'client_id' => 'lims_nankai',
    'shortName' => 'lims_nankai',
    'url' => 'http://less.nankai.edu.cn/lims/',
    'description' => '南开大学大型仪器管理系统', 
    'platform' => '17kong',
    'api' => [
        'godiva' => [
            'entry' => 'http://less.nankai.edu.cn/lims/api', 
            'dimension' => 'analysis/criteria',
            'type' => 'rpc'
        ],
    ],
];
