<?php

// 房间列表
$config['room'] = [
    'fields' => [
        'name' => ['type' => 'varchar(255)','null' => false,'default' => ''],
        'code' => ['type' => 'varchar(255)','null' => false,'default' => ''], // 唯一标识, 可不填
        'ctime' => ['type' => 'int','null' => false,'default' => 0],
        'status' => ['type' => 'tinyint','null' => false,'default' => 0],
    ],
    'indexes' => [
        'name' => ['fields' => ['name']],
        'code' => ['fields' => ['code']],
        'ctime' => ['fields' => ['ctime']],
        'status' => ['fields' => ['status']],
    ],
];

// 房间资源
$config['room_resource'] = [
    'room' => ['type'=>'object', 'oname' => 'room'], // 一般是equipment
    'resource' => ['type'=>'object'], // 一般是equipment
    'name' => ['type' => 'varchar(255)','null' => false,'default' => ''], // source->name的副本
    'coordinate_x' => ['type' => 'double', 'null' => FALSE, 'default' => 0],
    'coordinate_y' => ['type' => 'double', 'null' => FALSE, 'default' => 0],
    'coordinate_z' => ['type' => 'double', 'null' => FALSE, 'default' => 0],
];
