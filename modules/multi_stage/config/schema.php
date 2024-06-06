<?php
$config['site'] = [
    'fields' => [
        'name' => ['type' => 'varchar(100)', 'null' => FALSE, 'default' => ''],
        'site_id' => ['type' => 'varchar(100)', 'null' => FALSE, 'default' => ''],
        'lab_id' => ['type' => 'varchar(100)', 'null' => FALSE, 'default' => ''],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'atime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'description' => ['type' => 'text', 'null' => TRUE],
    ],
    'indexes' => [
        'name' => ['fields' => ['name']],
        'site_id' => ['fields' => ['site_id']],
        'lab_id' => ['fields' => ['lab_id']],
        'ctime' => ['fields'=>['ctime']],
    ],
];

// merge过程中记录ID变化，排错使用
$config['old_data'] = [
    'fields' => [
        'new_id' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'old_id' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'site_id' => ['type' => 'varchar(100)', 'null' => FALSE, 'default' => ''],
        'lab_id' => ['type' => 'varchar(100)', 'null' => FALSE, 'default' => ''],
        'obj' => ['type' => 'object'],
        'flag' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0],
],
    'indexes' => [
        'new_id' => ['fields' => ['new_id']],
        'old_id' => ['fields' => ['old_id']],
        'site_id' => ['fields' => ['site_id']],
        'lab_id' => ['fields' => ['lab_id']],
        'flag' => ['fields' => ['flag']],
    ],
];

$config['tag']['fields']['source_id'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['tag']['fields']['source_name'] = ['type' => 'varchar(40)', 'null' => FALSE, 'default' => ''];
$config['tag']['indexes']['source_id'] = ['fields' => ['source_id']];
$config['tag']['indexes']['source_name'] = ['fields' => ['source_name']];

$config['user']['fields']['source_id'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['user']['fields']['source_name'] = ['type' => 'varchar(40)', 'null' => FALSE, 'default' => ''];
$config['user']['indexes']['source_id'] = ['fields' => ['source_id']];
$config['user']['indexes']['source_name'] = ['fields' => ['source_name']];

$config['lab']['fields']['source_id'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['lab']['fields']['source_name'] = ['type' => 'varchar(40)', 'null' => FALSE, 'default' => ''];
$config['lab']['indexes']['source_id'] = ['fields' => ['source_id']];
$config['lab']['indexes']['source_name'] = ['fields' => ['source_name']];

$config['equipment']['fields']['source_id'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['equipment']['fields']['source_name'] = ['type' => 'varchar(40)', 'null' => FALSE, 'default' => ''];
$config['equipment']['indexes']['source_id'] = ['fields' => ['source_id']];
$config['equipment']['indexes']['source_name'] = ['fields' => ['source_name']];

$config['eq_evaluate']['fields']['source_id'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['eq_evaluate']['fields']['source_name'] = ['type' => 'varchar(40)', 'null' => FALSE, 'default' => ''];
$config['eq_evaluate']['indexes']['source_id'] = ['fields' => ['source_id']];
$config['eq_evaluate']['indexes']['source_name'] = ['fields' => ['source_name']];
