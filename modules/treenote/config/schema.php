<?php

$config['tn_project'] = [
	'fields' => [
		'title' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''], /* 标题 */
		'description' => ['type' => 'varchar(500)', 'null' => FALSE, 'default' => ''], /* 描述 */
		'user' => ['type' => 'object', 'oname' => 'user'], /* 负责人 */
		'is_complete' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0], /* 是否完成 */
		'is_locked' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0], /* 是否锁定 */
		'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0], /* 创建时间 */
		'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0], /* 修改时间 */
		],
	'indexes' => [
		'title' => ['fields' => ['title']],
		'user' => ['fields' => ['user']],
		'is_complete' => ['fields' => ['is_complete']],
		'is_locked' => ['fields' => ['is_locked']],
		'ctime' => ['fields' => ['ctime']],
		'mtime' => ['fields' => ['mtime']],
		]
	];

$config['tn_task'] = [
	'fields' => [
		'title' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''], /* 标题 */
		'description' => ['type' => 'text', 'null' => TRUE], /* 描述 */
		'user' => ['type' => 'object', 'oname' => 'user'], /* 负责人 */
		'reviewer' => ['type' => 'object', 'oname' => 'user'], /* 负责人 */
		'project' => ['type' => 'object', 'oname' => 'tn_project'], /* 项目 */
		'parent_task' => ['type' => 'object', 'oname' => 'tn_task'], /* 父任务 */
		'deadline' => ['type' => 'int', 'null' => FALSE, 'default' => 0], /* 限定日期 */
		'expected_time' => ['type' => 'int', 'null' => FALSE, 'default'=>0],
		'priority' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0], /* 优先级 */
		'status' => ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0], // 状态: Y P C
		'is_complete' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0], /* 是否完成 */
		'is_locked' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0], /* 是否锁定 */
		'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0], /* 创建时间 */
		'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0], /* 修改时间 */
		],
	'indexes' => [
		'title' => ['fields' => ['title']],
		'user' => ['fields' => ['user']],
		'reviewer' => ['fields' => ['reviewer']],
		'project' => ['fields' => ['project']],
		'parent_task' => ['fields' => ['parent_task']],
		'deadline' => ['fields' => ['deadline']],
		'priority' => ['fields' => ['priority']],
		'status' => ['fields' => ['status']],
		'is_complete' => ['fields' => ['is_complete']],
		'is_locked' => ['fields' => ['is_locked']],
		'ctime' => ['fields' => ['ctime']],
		'mtime' => ['fields' => ['mtime']],
		]
	];

$config['tn_note'] = [
	'fields' => [
		'content' => ['type' => 'text', 'null' =>TRUE], /* 描述 */
		'user' => ['type' => 'object', 'oname' => 'user'], /* 负责人 */
		'task' => ['type' => 'object', 'oname' => 'tn_task'], /* 父任务 */
		'project' => ['type' => 'object', 'oname' => 'tn_project'], // 所属项目
		'is_locked' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0], /* 是否锁定 */
		'actual_time' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0], /* 创建时间 */
		'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0], /* 修改时间 */
		],
	'indexes' => [
		'user' => ['fields' => ['user']],
		'task' => ['fields' => ['task']],
		'project' => ['fields' => ['project']],
		'is_locked' => ['fields' => ['is_locked']],
		'ctime' => ['fields' => ['ctime']],
		'mtime' => ['fields' => ['mtime']],
		],
	];

$config['tn_locker'] = [
	'fields' => [
		'user' => ['type' => 'object', 'oname' => 'user'], /* 锁定人 */
		'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0], /* 创建时间 */
		'task' => ['type' => 'object', 'oname' => 'tn_task'],
		],
	'indexes' => [
		'unique'=> ['type'=> 'unique', 'fields'=>['user', 'task']],
		'task' => ['fields' => ['task']],
		'ctime' => ['fields' => ['ctime']],
		],
	];
