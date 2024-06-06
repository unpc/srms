<?php

// 仪器可检测元素列表
$config['eq_element'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'name' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'price' => ['type' => 'double', 'null' => false, 'default' => 0],
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
        'name' => ['fields' => ['name']],
        'price' => ['fields' => ['price']],
    ],
];

// 检测元素记录表
$config['sample_element'] = [
    'fields' => [
        'remote_id' => ['type'=>'int', 'null'=>false, 'default'=>0],
        'eq_element' => ['type' => 'varchar(50)', 'null'=>false, 'default' => ''],
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'user' => ['type' => 'object', 'oname' => 'user'],
        'user_name' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'inspector' => ['type' => 'object', 'oname' => 'user'],
        'project_name' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'project_ref' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'ref' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'status' => ['type'=>'int', 'null'=>false, 'default'=>0],
        'count' => ['type'=>'int', 'null'=>false, 'default'=>0],
        'ctime' => ['type'=>'int', 'null'=>false, 'default'=>0],
        'result' => ['type'=>'text', 'null'=>true],
        'source' => ['type' => 'object'],
        'price' => ['type' => 'double', 'null' => false, 'default' => 0],
    ],
    'indexes' => [
        'remote_id' => ['fields' => ['remote_id']],
        'eq_element' => ['fields' => ['eq_element']],
        'equipment' => ['fields' => ['equipment']],
        'user' => ['fields' => ['user']],
        'user_name' => ['fields' => ['user_name']],
        'inspector' => ['fields' => ['inspector']],
        'status' => ['fields' => ['status']],
        'ctime' => ['fields' => ['ctime']],
    ],
];

$config['eq_record']['fields']['sample_element'] = ['type'=>'object', 'oname' => 'sample_element'];
$config['eq_record']['indexes']['sample_element'] = ['fields' => ['sample_element']];
