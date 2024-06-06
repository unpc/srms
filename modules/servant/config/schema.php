<?php
$config['platform'] = [
    'fields' => [
        'name' => ['type' => 'varchar(100)', 'null' => FALSE, 'default' => ''],
        'source_site' => ['type' => 'varchar(100)', 'null' => FALSE, 'default' => ''],
        'source_lab' => ['type' => 'varchar(100)', 'null' => FALSE, 'default' => ''],
        'code' => ['type' => 'varchar(100)', 'null' => FALSE, 'default' => ''],
        'contact' => ['type' => 'varchar(50)', 'null' => FALSE, 'default' => ''],
        'address' => ['type' => 'varchar(500)', 'null' => FALSE, 'default' => ''],
        'creator' => ['type' => 'object', 'oname' => 'user'],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'atime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'description' => ['type' => 'text', 'null' => TRUE],
    ],
    'indexes' => [
        'name' => ['fields' => ['name']],
        'source_site' => ['fields' => ['source_site']],
        'source_lab' => ['fields' => ['source_lab']],
        'code' => ['fields' => ['code']],
        'contact' => ['fields' => ['contact']],
        'address' => ['fields' => ['address']],
        'creator' => ['fields' => ['creator']],
        'ctime' => ['fields'=>['ctime']],
        'atime' => ['fields'=>['atime']],
    ],
];
