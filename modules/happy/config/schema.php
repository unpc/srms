<?php

$config['happyhour'] = [
	'fields' => [
		'creater'=>['type'=>'object', 'oname'=>'user'],
		'title'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'body'=>['type'=>'text', 'null'=>FALSE, 'default'=>''],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'creater'=>['fields'=>['creater']],
		'title'=>['fields'=>['title']],
		'ctime'=>['fields'=>['ctime']],
		'dtime'=>['fields'=>['dtime']],
	],
];
$config['happy_reply']=[
    'fields' => [
		'happyhour'=>['type'=>'object', 'oname'=>'happyhour'],
		'replyer'=>['type'=>'object', 'oname'=>'user'],
		'content'=>['type'=>'text', 'null'=>FALSE, 'default'=>''],
		'stock'=>['type'=>'int','null'=>FALSE,'default'=>'1'],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'happyhour'=>['type'=>'unqiue', 'fields'=>['replyer', 'happyhour']],
		'ctime'=>['fields'=>['mtime']],
	],
];
$config['happy_stock']=[
	'fields' => [
		'happyhour'=>['type'=>'object', 'oname'=>'happyhour'],
		'content'=>['type'=>'text', 'null'=>FALSE, 'default'=>''],
		'count'=>['type'=>'int','null'=>FALSE,'default'=>1],
	],
	'indexes' => [
		'content'=>['fields'=>['content']],
	],
];
