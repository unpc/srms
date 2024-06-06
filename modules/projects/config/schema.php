<?php

$config['task'] = [
	'fields' => [
		'name' => ['type' => 'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'description' => ['type' => 'varchar(250)', 'null'=>TRUE],
		'canceled' => ['type' => 'int', 'null'=>FALSE, 'default'=>0], // 是否删除到回收站 [0,1]. 值为1点击删除，从数据库删除
		'milestone' => ['type' => 'int', 'null'=>FALSE, 'default'=>0], // 是否作为里程碑，用户计算父级task的工作进度
		'parent' => ['type' => 'object', 'oname' => 'task'], 
		'prev' => ['type' => 'object', 'oname' => 'task'],
		'next' => ['type' => 'object', 'oname' => 'task'],
		'type' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // [container|task]
		'dtstart' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 预计开始时间
		'dtrealstart' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 实际开始时间
		'dtend' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 预计结束时间
		'dtrealend' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 实际结束时间
		'approved' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // [0:未结束, 1:结束]
		'complete'=> ['type'=>'int', 'null' => FALSE , 'default'=>0], //[0:未完成, 1:完成]   
		'locked' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // [0:不锁定; 1:仅锁定日期; 2: 全锁定]
	],
	'indexes' => [
		'name' => ['fields' => ['name']],
		'parent' => ['fields' => ['parent']],
		'prev' => ['fields' => ['prev']],
		'next' => ['fields' => ['next']],
	]];

$config['project'] = [
	'fields' => [
	/*
		'name' => array('type' => 'varchar(150)', 'null'=>FALSE, 'default'=>''),
		'description' => array('type' => 'varchar(250)', 'null'=>TRUE),
		'canceled' => array('type' => 'int', 'null'=>FALSE, 'default'=>0), // 是否删除到回收站 [0,1]. 值为1点击删除，从数据库删
		'dtstart' => array('type' => 'int', 'null' => FALSE, 'default' => 0), // 预计开始时间
		'dtrealstart' => array('type' => 'int', 'null' => FALSE, 'default' => 0), // 实际开始时间
		'dtend' => array('type' => 'int', 'null' => FALSE, 'default' => 0), // 预计结束时间
		'dtrealend' => array('type' => 'int', 'null' => FALSE, 'default' => 0), // 实际结束时间
		'approved' => array('type' => 'int', 'null' => FALSE, 'default' => 0), // [0:未结束, 1:结束]
		'locked' => array('type' => 'int', 'null' => FALSE, 'default' => 0), // [0:不锁定; 1:仅锁定日期; 2: 全锁定]
	*/
		'task' => ['type' => 'object', 'oname' => 'task'], // project 指向的 task container
	],
	'indexes' => [
		'task' => ['fields' => ['task']],
	]];
		

