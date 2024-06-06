<?php
$config['role']['fields']['name'] = ['type' => 'varchar(150)', 'null' => FALSE];
$config['role']['fields']['weight'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['role']['fields']['connect_perms_time'] = ['type' => 'int', 'null' => TRUE, 'default' => 0];
$config['role']['indexes']['weight'] = ['fields' => ['weight']];

$config['perm']['fields']['module'] = ['type' => 'object', 'oname' => 'module'];
$config['perm']['fields']['sub_module'] = ['type' => 'object', 'oname' => 'sub_module'];
$config['perm']['fields']['name'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['perm']['fields']['weight'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['perm']['indexes']['module'] = ['fields' => ['module']];
$config['perm']['indexes']['sub_module'] = ['fields' => ['sub_module']];
$config['perm']['indexes']['name'] = ['fields' => ['name']];
$config['perm']['indexes']['weight'] = ['fields' => ['weight']];

$config['sub_module'] = [
	'fields' => [
		'module' => ['type' => 'object', 'oname' => 'module'],
		'name' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
		'weight' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'description' => ['type' => 'text', 'null' => TRUE]
	],
	'indexes' => [
		'name' => ['type' => 'unique', 'fields' => ['name', 'module']],
		'weight' => ['fields' => ['weight']],
		'module' => ['fields' => ['module']]
	]
];
