<?php

$config['gapper_fallback_user'] = [
	'fields' => [
		'user' => ['type'=>'object', 'oname'=>'user'],
		'token' => ['type'=>'varchar(100)', 'null'=>FALSE, 'default'=>''],
	],
	'indexes' => [
		'user' => ['fields'=>['user'], 'type'=>'unique'],
		'token'=>['fields'=>['token'], 'type'=>'unique'],
	],
];
