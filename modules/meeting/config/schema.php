<?php

$config['cal_component']['fields']['me_room'] = ['type'=>'object', 'oname' => 'meeting'];
$config['cal_component']['indexes']['me_room'] = ['fields'=>['me_room']];

$config['meeting'] = [
	'fields' => [
		'name' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'name_abbr' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
		'ref_no' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
		'en_name' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'phone'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'location'=>['type'=>'object', 'oname'=>'tag_location'],
        'util_area' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'type' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'description' => ['type'=>'text', 'null'=>TRUE],
		'tag_root' => ['type'=>'object', 'oname'=>'tag'],
	],
	'indexes' => [
		'name' => ['fields' => ['name']],
		'ref_no' => ['fields' => ['ref_no']],
		'ctime' => ['fields' => ['ctime']],
		'type' => ['fields' => ['type']],
		'location' => ['fields' => ['location']],
	],
];

$config['me_reserv'] = [
	'fields' => [
		'component' => ['type'=>'object', 'oname'=>'cal_component'],
		'meeting' => ['type'=>'object', 'oname' => 'meeting'],
		'user' => ['type'=>'object', 'oname' => 'user'],
		'type' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'users' => ['type'=>'text', 'null' => FALSE, 'default'=>''],
		'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'is_check' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'status' => ['type'=>'int', 'null'=>FALSE, 'default'=>0]
	],
	'indexes' => [
		'component' => ['fields' => ['component']],
		'meeting' => ['fields' => ['meeting']],
		'user' => ['fields' => ['user']],
		'dtstart' => ['fields' => ['dtstart']],
		'dtend' => ['fields' => ['dtend']],
		'is_check' => ['fields' => ['is_check']],
		'type' => ['fields' => ['type']],
		'status' => ['fields' => ['status']]
	],
];

$config['meeting_announce'] = [
    'fields' => [
		'title' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
		'content' => ['type' => 'text'],
		'author' => ['type' => 'object', 'oname' => 'user'],
		'meeting' => ['type' => 'object', 'oname' => 'meeting'],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'is_sticky'=>['type'=>'tinyint(1)', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'meeting' => ['fields' => ['meeting']],
		'author' => ['fields' => ['author']],
		'title' => ['fields' => ['title']],
	],
];

$config['um_auth'] = [
	'fields' => [
		'user' => ['type' => 'object', 'oname' => 'user'],
		'tag' => ['type' => 'object', 'oname' => 'tag'],
		'meeting'=>['type'=>'object', 'oname'=>'meeting'],
		'status' => ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'type'=>['type'=>'int', 'null'=>TRUE, 'default'=>0],
		'atime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'user_meeting' => ['fields' => ['user','meeting']],
		'tag' => ['fields' => ['tag']],
		'mtime'=>['fields'=>['mtime']],
		'status'=>['fields'=>['status']],
		'type'=>['fields'=>['type']],
	],
];


$config['tag_room']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default' => ''];
$config['tag_room']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_room']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_location'];
$config['tag_room']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_location'];
$config['tag_room']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_room']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_room']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_room']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_room']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];

$config['tag_room']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_room']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_room']['indexes']['root'] = ['fields'=>['root']];
$config['tag_room']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_room']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_room']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_room']['indexes']['code'] = ['fields'=>['code']];