<?php

$config['message'] = [
	'fields' => [
		'sender'=>['type'=>'object', 'oname'=>'user'],
		'receiver'=>['type'=>'object', 'oname'=>'user'],
		'title'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'body'=>['type'=>'text', 'null' => TRUE],
		'is_read'=>['type'=>'tinyint(1)', 'null'=>FALSE, 'default'=>0],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'sender'=>['fields'=>['sender']],
		'receiver'=>['fields'=>['receiver']],
		'is_read' => ['fields' => ['is_read']],
		'title'=>['fields'=>['title']],
		'mtime'=>['fields'=>['mtime']],
	],
];

