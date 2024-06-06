<?php

$config['update'] = [
	'fields' => [
		'subject' => ['type'=>'object', 'oname'=>'user'],
		'object' => ['type'=>'object'],
		'new_data' => ['type'=>'text','null'=>FALSE],
		'old_data'=>['type'=>'text', 'null'=>FALSE],
		'action' => ['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>' '],
		'ctime' => ['type'=>'int','null'=>FALSE,'default'=>0],
	],
	
	'indexes' => [
		'ctime' => ['fields'=>['ctime']],
	],
];

