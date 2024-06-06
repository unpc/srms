<?php

$config['vote_activity'] = [
	'fields' => [
		'creater' => ['type' => 'object', 'oname' => 'user'],
		'topic' => ['type' => 'varchar(150)','null' => FALSE,'default' => ''],
		'ctime' => ['type' => 'int','null' => FALSE,'default' => 0],
		'mtime' => ['type' => 'int','null' => FALSE,'default' => 0],
		'radio' => ['type' => 'int','null' => FALSE,'default' => 1],//1--单选，0--多选
		'dtstart' => ['type' => 'int','null' => FALSE,'default' => 0],//投票开始时间
		'dtend' => ['type' => 'int','null' => FALSE,'default' => 0],//投票截止时间
		'choices' => ['type' => 'varchar(250)','null' => FALSE,'default' => ''],
		'remark' => ['type' => 'varchar(250)','null' => FALSE,'default' => '']
		
	],

	'indexes' => [
		'creater' => ['fields' => ['creater']],
		'topic' => ['fields' => ['topic']],
		'ctime' => ['fields' => ['ctime']]
	]
];

$config['vote_behavior'] = [
	'fields' => [
		'creater' => ['type' => 'object', 'oname' => 'user'],
		'vote_activity' => ['type' => 'object','oname' => 'vote_activity'],
		'ctime' => ['type' => 'int','null' => FALSE,'default' => 0],
		'choice' => ['type' => 'varchar(250)','null' => FALSE,'default' => '']
	],
	'indexes' => [
		'creater' => ['fields' => ['creater']],
		'vote_activity' => ['fields' => ['vote_activity']],
		'choice' => ['fields' => ['choice']]	
	]

];
