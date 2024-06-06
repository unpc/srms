<?php

$config['eq_sample']['fields']['stime'] = ['type'=>'int', 'null'=>TRUE];
$config['eq_sample']['indexes']['stime'] = ['fields'=>['stime']];

$config['eq_sample']['fields']['name'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['eq_sample']['indexes']['name'] = ['fields'=>['name']];

$config['eq_sample']['fields']['type'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['eq_sample']['indexes']['type'] = ['fields'=>['type']];

$config['eq_sample']['fields']['code'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['eq_sample']['indexes']['code'] = ['fields'=>['code']];

$config['eq_sample']['fields']['format'] = ['type'=>'int', 'null'=>false, 'default'=>1];
$config['eq_sample']['indexes']['format'] = ['fields'=>['format']];

$config['eq_sample']['fields']['mode'] = ['type' => 'varchar(150)', 'null' => false, 'default' => ''];
$config['eq_sample']['indexes']['mode'] = ['fields'=>['mode']];

$config['eq_sample']['fields']['fir_trial'] = ['type'=>'object', 'oname'=>'user'];
$config['eq_sample']['indexes']['fir_trial'] = ['fields'=>['fir_trial']];

$config['eq_sample']['fields']['sec_trial'] = ['type'=>'object', 'oname'=>'user'];
$config['eq_sample']['indexes']['sec_trial'] = ['fields'=>['sec_trial']];

$config['sample_result'] = [
	'fields' => [
		'sample' => ['type'=>'object', 'oname'=>'eq_sample'],
        'subname' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'parameter' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'concentration' =>  ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'level' =>  ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'result' =>  ['type' => 'varchar(150)', 'null' => TRUE],
	],
	'indexes' => [
        'sample'=>['fields'=>['sample']],
	],
];

$config['sample_remark'] = [
    'fields' => [
		'sample' => ['type'=>'object', 'oname'=>'eq_sample'],
        'content' => ['type' => 'varchar(250)', 'null' => FALSE, 'default' => ''],
        'type' => ['type'=>'int', 'null'=>FALSE, 'default'=>1],
        'time' => ['type'=>'int', 'null'=>FALSE, 'default'=>1],
    ],
	'indexes' => [
        'sample'=>['fields'=>['sample']],
	],
];
