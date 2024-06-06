<?php

//仪器预约审批限制
$config['eq_quota'] = [
	'fields' => [
		'user' => ['type'=>'object', 'oname'=>'user'],
		'type'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'value'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0]
	],
	'indexes' => [
		'user'=>['type'=>'unqiue', 'fields'=>['user']],
		'type'=>['fields'=>['type']],
		'value'=>['fields'=>['value']]
	],
];

//仪器预约劵
$config['eq_voucher'] = [
	'fields' => [
		'user' => ['type' => 'object', 'oname' => 'user'],
		'lab' => ['type' => 'object', 'oname' => 'lab'],
		'equipment' => ['type' => 'object', 'oname' => 'equipment'],
		'project' => ['type' => 'object', 'oname' => 'lab_project'],
		'type' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'status' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'use_status' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'used_time' => ['type' => 'float', 'null' => FALSE, 'default' => 0],
		'auto_amount' => ['type' => 'float', 'null' => FALSE, 'default' => 0],
		'samples' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'hide' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
	],
	'indexes' => [
		'user'=>['fields'=>['user']],
		'lab'=>['fields'=>['lab']],
		'equipment'=>['fields'=>['equipment']],
		'project'=>['fields'=>['project']],
		'type'=>['fields'=>['type']],
		'status'=>['fields'=>['status']],
		'use_status'=>['fields'=>['use_status']],
		'auto_amount'=>['fields'=>['auto_amount']],
		'samples'=>['fields'=>['samples']],
		'hide'=>['fields'=>['hide']],
		'ctime'=>['fields'=>['ctime']],
		'mtime'=>['fields'=>['mtime']]
	],
];