<?php

$config['application'] = [
    'api' => [
        'godiva' => [
            'entry' => 'http://17kong.labscout.cn/lims/api', 
            'dimension' => 'analysis/criteria',
            'type' => 'rpc'
        ],
        'dashboard' => [
            'entry' => 'http://17kong.labscout.cn/lims/api',
            'type' => 'rpc'
        ]
    ],
    'client_id' => 'lims',
    'name' => '大型仪器管理系统',
    'shortName' => '大仪平台',
    'url' => 'http://17kong.labscout.cn/lims/',
];