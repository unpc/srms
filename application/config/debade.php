<?php

$config['secret'] = '400MYGENEE';

$config['queues'] = [
    'YiQiKong'=> [
        'driver'=> 'Courier',
        'options'=> [
            'dsn'=> 'tcp://172.17.42.1:3333',
            'queue'=> 'YiQiKong',
        ],
    ],
    'Lims-CF'=> [
        'driver'=> 'Courier',
        'options'=> [
            'dsn'=> 'tcp://172.17.42.1:3333',
            'queue'=> 'Lims-CF',
        ],
    ],
];
