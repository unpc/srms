<?php

$config['eq_struct'] = [
	'fields' => [
		'name'=>['type'=>'varchar(150)', 'null'=>FALSE],
		'description'=>['type'=>'text', 'null'=>TRUE],
		'type'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0]
	],
	'indexes' => [
		'name'=>['type'=>'unique', 'fields'=>['name']],
		'type'=>['fields'=>['type']],
		'ctime'=>['fields'=>['ctime']],
		'mtime'=>['fields'=>['mtime']],
	],
];

$config['cers_share_data'] = [
	'fields' => [
		'equipment'=>['type'=>'object', 'oname'=>'equipment'],
		'from_year' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'to_year' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'description'=>['type'=>'text', 'null'=>TRUE],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0]
	],
	'indexes' => [
		'equipment'=>['type'=>'unique', 'fields'=>['equipment']],
		'from_year'=>['fields'=>['from_year']],
		'to_year'=>['fields'=>['to_year']],
		'ctime'=>['fields'=>['ctime']],
		'mtime'=>['fields'=>['mtime']],
	],
];

$config['equipment']['fields']['struct'] = ['type'=>'object', 'oname'=>'eq_struct'];
$config['equipment']['indexes']['struct'] = ['fields'=>['struct']];

$config['equipment']['fields']['domain'] = ['type' => 'varchar(250)', 'null' => TRUE ];
$config['equipment']['indexes']['domain'] = ['fields' => ['domain']];