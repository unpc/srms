<?php

$config['file_cache'] = [
    'fields' => [
        'file_path' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'file_size' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'dctime' => ['type' => 'varchar(50)', 'null' => FALSE, 'default' => ''],
    ],
    'indexes' => [
        'file_path' => ['type' => 'unique', 'fields' => ['file_path']],
        'file_size' => ['fields' => ['file_path']],
        'dctime' => ['fields' => ['dctime']],
    ]
];
