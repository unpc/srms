<?php

//工作流程
$config['workflow'] = [
	'fields' => [
		'name' => ['type' => 'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'abbr' => ['type' => 'varchar(40)', 'null'=> FALSE, 'default'=>''],
	],
	'indexes' => [
		'abbr' => ['fields' => ['abbr'], 'type'=>'unique'],
		'name' => ['fields' => ['name']],
	]
];

//工作流程 节点
$config['workflow_node'] = [
	'fields' => [
		'name' => ['type' => 'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'role' => ['type' => 'object', 'oname' => 'workflow_role'],
	],
	'indexes' => [
		'name' => ['fields' => ['name']],
		'role' => ['fields' => ['role']],
	]
];

//relationship: .prev, .next

$config['workflow_form'] = [
	'fields' => [
		'name' => ['type' => 'varchar(150)', 'null'=>FALSE, 'default'=>''],
	],
	'indexes' => [
		'name' => ['fields' => ['name']],
	]
];

//工作流程 角色
$config['workflow_role'] = [
	'fields' => [
		'name' => ['type' => 'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'workflow' => ['type' => 'object', 'oname' => 'workflow'],
	],
	'indexes' => [
		'name' => ['fields' => ['name']],
	]
];

/*
 * 表单type
 *	0	Text Field
 *	1	Check Box
 *	2	Radio
 *	3	Dropdown
 *	4	Role Approval
 *	5	Attachment
 */
$config['workflow_form_field'] = [
	'fields' => [
		'label' => ['type' => 'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'form' => ['type' => 'object', 'oname' => 'workflow_form'],
		'type' => ['type' => 'tinyint', 'null'=>FALSE, 'default' => 0],
	],
	'indexes' => [
		'label' => ['fields' => ['label']],
	]
];

