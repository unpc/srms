<?php

$config['schedule_speaker'] = [
	'fields' => [
		'component' => ['type'=>'object','oname'=>'cal_component'],
		'user' => ['type'=>'object','oname'=>'user'],
		'name' => ['type'=>'varchar(50)','null'=>FALSE,'default'=>''],
	],
	
	'indexes' => [
		'speaker' => ['fields'=>['component','user']],
	],
];

$config['sch_att_user'] = [
	'fields' => [
		'component' => ['type'=>'object','oname'=>'cal_component'],
		'user' => ['type'=>'object','oname'=>'user'],
		'name' => ['type'=>'varchar(50)','null'=>FALSE,'default'=>''],
	],
	
	'indexes' => [
		'attendee' => ['fields'=>['component','user']],
	],
];

$config['sch_att_role'] = [
	'fields' => [
		'component' => ['type'=>'object','oname'=>'cal_component'],
		'role_id' => ['type'=>'varchar(50)','null'=>FALSE,'default'=>''],
	],
	
	'indexes' => [
		'roles' => ['fields'=>['component','role_id']],
	],
];

$config['sch_att_group'] = [
	'fields' => [
		'component' => ['type'=>'object','oname'=>'cal_component'],
		'group_id' => ['type'=>'varchar(50)','null'=>FALSE,'default'=>''],
	],
	
	'indexes' => [
		'groups' => ['fields'=>['component','group_id']],
	],
];

/*
$config['schedule_type'] = array(
	'fields' => array(
		'component' => array('type'=>'object','oname'=>'cal_component'),
		'user' => array('type'=>'varchar(50)','null'=>FALSE,'default'=>''),
		'group' => array('type'=>'varchar(50)','null'=>FALSE,'default'=>''),
		'role' => array('type'=>'varchar(50)','null'=>FALSE,'default'=>'')
	),
);
*/

