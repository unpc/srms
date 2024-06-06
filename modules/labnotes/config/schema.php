<?php

$config['note'] = [
	'fields' => [
		'title' => ['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''],
		'owner' => ['type'=>'object', 'name'=>'user'],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'lock' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'title' => ['fields'=>['title']],
		'ctime' => ['fields'=>['ctime']],
		'lock' => ['fields'=>['lock']],
	],
];


