<?php

// 仪器耗材计量单位
$config['material_unit'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'name' => ['type' => 'varchar(150)', 'null' => false],
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
        'name' => ['fields'=>['name']],
        'unique'=>['fields'=>['name','equipment']],
    ],
];

// 仪器耗材
$config['material'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'material_unit' => ['type' => 'object', 'oname' => 'material_unit'],
        'name' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'price' => ['type' => 'double', 'null' => false, 'default' => 0],
        'enable_use' => ['type'=>'int', 'null'=>false, 'default'=>1],
        'enable_sample' => ['type'=>'int', 'null'=>false, 'default'=>1],
        'hidden' => ['type'=>'int', 'null'=>false, 'default'=>0],
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
        'material_unit' => ['fields' => ['material_unit']],
        'name' => ['fields' => ['name']],
        'enable_use' => ['fields' => ['enable_use']],
        'enable_sample' => ['fields' => ['enable_sample']],
    ],
];

//预约/送样/使用 选用耗材字段 一条记录可选多个耗材 故存json格式
$config['eq_reserv']['fields']['materials'] = ['type'=> 'text', 'null'=> TRUE];
$config['eq_sample']['fields']['materials'] = ['type'=> 'text', 'null'=> TRUE];
$config['eq_record']['fields']['materials'] = ['type'=> 'text', 'null'=> TRUE];

//耗材费
$config['eq_charge']['fields']['material_amount'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];