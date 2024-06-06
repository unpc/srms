<?php

$config['eq_wait_join'] = [
	'fields' => [
		# 对应用户
		'user' => ['type'=>'object', 'oname'=>'user'],
		'equipment' => ['type'=>'object', 'oname'=>'equipment'],
		# 排队预约请求情况
		'description' => ['type'=>'text', 'null'=>TRUE],
		'sample' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'status' => ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
		'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'time' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'time_format' => ['type' => 'varchar(10)', 'null' => false, 'default' => 'h']
	],
	'indexes' => [
		'user' => ['fields'=>['user']],
		'equipment' => ['fields'=>['equipment']],
		'ctime' => ['fields' => ['ctime']],
		'status' => ['fields' => ['status']],
		'time' => ['fields' => ['time']],
		'time_format' => ['fields' => ['time_format']]
	],
];
