<?php

$config['material'] = [
	'fields' => [
		'name' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'name_abbr' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'type' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'user' => ['type'=>'object', 'oname'=>'user'],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'description' => ['type'=>'text', 'null'=>TRUE]
	],
	'indexes' => [
		'name' => ['fields' => ['name']],
		'ctime' => ['fields' => ['ctime']],
		'type' => ['fields' => ['type']],
		'user' => ['fields' => ['user']]
	],
];

$config['broadcast'] = [
	'fields' => [
		'name' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'name_abbr' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'type' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'status' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'user' => ['type'=>'object', 'oname'=>'user'],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'description' => ['type'=>'text', 'null'=>TRUE]
	],
	'indexes' => [
		'name' => ['fields' => ['name']],
		'ctime' => ['fields' => ['ctime']],
		'type' => ['fields' => ['type']],
        'status' => ['fields' => ['status']],
	],
];