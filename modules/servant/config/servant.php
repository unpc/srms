<?php
$config['disable_code'] = [
    'billing-later',
    'multimedia-admin',
    'platform',
    'public',
    'sj',
    'socket.io',
    'stat',
];

$config['optional_modules'] = [
    'achievements'=> [
        'title'=> '成果管理',
        'default'=> TRUE,
    ],
    'eq_stat'=> [
        'title'=> '仪器统计',
        'default'=> TRUE,
    ],
    'cers'=> [
        'title'=> 'CERS',
        'default'=> TRUE,
    ],
    'billing_later'=> [
        'title'=> '报销管理',
        'modules'=> [
            'billing',
            'billing_later',
        ],
        'default'=> TRUE,
    ],
];