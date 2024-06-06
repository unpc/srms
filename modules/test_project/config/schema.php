<?php

// 测试项目分类
$config['test_project_cat'] = [
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

// 测试项目
$config['test_project'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'test_project_cat' => ['type' => 'object', 'oname' => 'test_project_cat'],
        'name' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'price' => ['type' => 'double', 'null' => false, 'default' => 0],
        'enable_use' => ['type'=>'int', 'null'=>false, 'default'=>1],
        'enable_sample' => ['type'=>'int', 'null'=>false, 'default'=>1],
        'hidden' => ['type'=>'int', 'null'=>false, 'default'=>0],
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
        'test_project_cat' => ['fields' => ['test_project_cat']],
        'name' => ['fields' => ['name']],
        'enable_use' => ['fields' => ['enable_use']],
        'enable_sample' => ['fields' => ['enable_sample']],
    ],
];

//选用测试项目字段 一条记录可选多个项目 故存json格式
$config['eq_reserv']['fields']['test_projects'] = ['type'=> 'text', 'null'=> TRUE];
$config['eq_sample']['fields']['test_projects'] = ['type'=> 'text', 'null'=> TRUE];
$config['eq_record']['fields']['test_projects'] = ['type'=> 'text', 'null'=> TRUE];

//测试项目费
$config['eq_charge']['fields']['test_project_amount'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];