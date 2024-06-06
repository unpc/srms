<?php

// $config['module'] = [
// 	'fields' => [
// 		'mid'=>['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''],
// 		'name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
// 		'category'=>['type'=>'object', 'oname'=>'module_category'],
// 		'sversion'=>['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''],
// 		'dversion'=>['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''],
// 		'cversion'=>['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''],
// 		'description'=>['type'=>'text', 'null'=>TRUE],
// 		'weight'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
// 	],
// 	'indexes' => [
// 		'mid'=>['type'=>'unique', 'fields'=>['mid']],
// 		'category'=>['fields'=>['category']],
// 		'sversion'=>['fields'=>['sversion']],
// 		'dversion'=>['fields'=>['dversion']],
// 		'cversion'=>['fields'=>['cversion']],
// 		'weight'=>['fields'=>['weight']],
// 	],
// ];

$config['module'] = [
	'fields' => [
		'mid' => ['type' => 'varchar(40)', 'null' => FALSE, 'default' => ''],
		'name' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
		'weight' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'description' => ['type' => 'text', 'null' => TRUE],
        'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0]
	],
	'indexes' => [
		'mid' => ['type' => 'unique', 'fields' => ['mid']],
		'name' => ['fields' => ['name']],
		'weight' => ['fields' => ['weight']],
        'ctime' => ['fields'=>['ctime']],
        'mtime' => ['fields'=>['mtime']]
	]
];

$config['tag']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default' => ''];
$config['tag']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag'];
$config['tag']['fields']['root'] = ['type'=>'object', 'oname'=>'tag'];
$config['tag']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag']['indexes']['root'] = ['fields'=>['root']];
$config['tag']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag']['indexes']['code'] = ['fields'=>['code']];

$config['recovery'] = [
	'fields' => [
		'key' => ['type'=>'varchar(150)', 'null'=>FALSE],
		'user' => ['type'=>'object', 'oname'=>'user'],
		'overdue' => ['type'=>'int', 'null'=>FALSE, 'default'=>0]
	],
	'indexes' => [
		'key' => ['fields'=>['key'], 'type'=>'unique'],
		'user' => ['fields'=>['user']],
		'overdue' => ['fields'=>['overdue']]
	],
];

$config['comment'] = [
	'fields' => [
        'content' => ['type'=>'varchar(500)', 'null'=>FALSE],
        'author' => ['type'=>'object', 'oname'=>'user'],
        'object' => ['type'=>'object'],
        'url_params' => ['type'=>'varchar(200)', 'null'=>FALSE, 'default'=> ''],
        'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0]
	],
	'indexes' => [
		'author' => ['fields'=>['author']],
		'ctime' => ['fields'=>['ctime']],
		'mtime' => ['fields'=>['mtime']]
	],
];

$config['notification'] = [
	'fields' => [
		'conf_key' => ['type'=>'varchar(250)', 'null'=>FALSE],
		'receiver' => ['type'=>'object'],
		'batch'=> ['type'=>'object', 'oname'=>'notification_batch'],
		'params' => ['type'=>'text', 'null'=>FALSE],
		'sender' => ['type'=>'object', 'oname'=>'user'],
		'vars' => ['type'=>'text', 'null'=>FALSE],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'batch' => ['fields'=>['batch']],
		'ctime' => ['fields'=>['ctime']],
	],
];

$config['notification_user'] = [
	'fields' => [
		'notification' => ['type'=>'object', 'oname'=>'notification'],
		'user' => ['type'=>'object', 'oname'=>'user'],
		'batch'=> ['type'=>'object', 'oname'=>'notification_batch'],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'unique'=>['fields'=>['user', 'batch'], 'type'=>'unique'],
		'notification' => ['fields'=>['notification']],
		'batch' => ['fields'=>['batch']],
		'user' => ['fields'=>['user']],
	],
];

$config['notification_batch'] = [
	'fields' => [
		'status' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'status' => ['fields'=>['status']]
	],
];

$config['user']['fields']['nl_cat_vis'] = ['type'=>'varchar(200)', 'null'=>FALSE, 'default'=> ''];

$config['tag_group']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default' => ''];
$config['tag_group']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_group']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_group'];
$config['tag_group']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_group'];
$config['tag_group']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_group']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_group']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_group']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_group']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag_group']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_group']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_group']['indexes']['root'] = ['fields'=>['root']];
$config['tag_group']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_group']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_group']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_group']['indexes']['code'] = ['fields'=>['code']];

$config['tag_equipment']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE];
$config['tag_equipment']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_equipment']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_equipment'];
$config['tag_equipment']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_equipment'];
$config['tag_equipment']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag_equipment']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_equipment']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_equipment']['indexes']['root'] = ['fields'=>['root']];
$config['tag_equipment']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_equipment']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_equipment']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_equipment']['indexes']['code'] = ['fields'=>['code']];

$config['tag_achievements_patent']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE];
$config['tag_achievements_patent']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_achievements_patent']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_achievements_patent'];
$config['tag_achievements_patent']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_achievements_patent'];
$config['tag_achievements_patent']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_patent']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_patent']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_patent']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_patent']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag_achievements_patent']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_achievements_patent']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_achievements_patent']['indexes']['root'] = ['fields'=>['root']];
$config['tag_achievements_patent']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_achievements_patent']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_achievements_patent']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_achievements_patent']['indexes']['code'] = ['fields'=>['code']];

$config['tag_achievements_award']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE];
$config['tag_achievements_award']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_achievements_award']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_achievements_award'];
$config['tag_achievements_award']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_achievements_award'];
$config['tag_achievements_award']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_award']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_award']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_award']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_award']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag_achievements_award']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_achievements_award']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_achievements_award']['indexes']['root'] = ['fields'=>['root']];
$config['tag_achievements_award']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_achievements_award']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_achievements_award']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_achievements_award']['indexes']['code'] = ['fields'=>['code']];

$config['tag_achievements_publication']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE];
$config['tag_achievements_publication']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_achievements_publication']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_achievements_publication'];
$config['tag_achievements_publication']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_achievements_publication'];
$config['tag_achievements_publication']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_publication']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_publication']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_publication']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_achievements_publication']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag_achievements_publication']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_achievements_publication']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_achievements_publication']['indexes']['root'] = ['fields'=>['root']];
$config['tag_achievements_publication']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_achievements_publication']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_achievements_publication']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_achievements_publication']['indexes']['code'] = ['fields'=>['code']];

$config['tag_equipment_user_tags']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE];
$config['tag_equipment_user_tags']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_equipment_user_tags']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_equipment_user_tags'];
$config['tag_equipment_user_tags']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_equipment_user_tags'];
$config['tag_equipment_user_tags']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_user_tags']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_user_tags']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_user_tags']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_user_tags']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag_equipment_user_tags']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_equipment_user_tags']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_equipment_user_tags']['indexes']['root'] = ['fields'=>['root']];
$config['tag_equipment_user_tags']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_equipment_user_tags']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_equipment_user_tags']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_equipment_user_tags']['indexes']['code'] = ['fields'=>['code']];

$config['tag_meeting_user_tags']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE];
$config['tag_meeting_user_tags']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_meeting_user_tags']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_meeting_user_tags'];
$config['tag_meeting_user_tags']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_meeting_user_tags'];
$config['tag_meeting_user_tags']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_meeting_user_tags']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_meeting_user_tags']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_meeting_user_tags']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_meeting_user_tags']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag_meeting_user_tags']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_meeting_user_tags']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_meeting_user_tags']['indexes']['root'] = ['fields'=>['root']];
$config['tag_meeting_user_tags']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_meeting_user_tags']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_meeting_user_tags']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_meeting_user_tags']['indexes']['code'] = ['fields'=>['code']];

$config['tag_location']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default' => ''];
$config['tag_location']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_location']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_location'];
$config['tag_location']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_location'];
$config['tag_location']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_location']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_location']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_location']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_location']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag_location']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_location']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_location']['indexes']['root'] = ['fields'=>['root']];
$config['tag_location']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_location']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_location']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_location']['indexes']['code'] = ['fields'=>['code']];

$config['tag_equipment_technical']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE];
$config['tag_equipment_technical']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_equipment_technical']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_equipment_technical'];
$config['tag_equipment_technical']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_equipment_technical'];
$config['tag_equipment_technical']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_technical']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_technical']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_technical']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_technical']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag_equipment_technical']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_equipment_technical']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_equipment_technical']['indexes']['root'] = ['fields'=>['root']];
$config['tag_equipment_technical']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_equipment_technical']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_equipment_technical']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_equipment_technical']['indexes']['code'] = ['fields'=>['code']];

$config['tag_equipment_education']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE];
$config['tag_equipment_education']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_equipment_education']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_equipment_education'];
$config['tag_equipment_education']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_equipment_education'];
$config['tag_equipment_education']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_education']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_education']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_education']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_equipment_education']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];
$config['tag_equipment_education']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_equipment_education']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_equipment_education']['indexes']['root'] = ['fields'=>['root']];
$config['tag_equipment_education']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_equipment_education']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_equipment_education']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_equipment_education']['indexes']['code'] = ['fields'=>['code']];