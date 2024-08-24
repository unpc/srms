<?php

$config['cal_component']['fields']['me_room'] = ['type'=>'object', 'oname' => 'meeting'];
$config['cal_component']['indexes']['me_room'] = ['fields'=>['me_room']];

$config['meeting'] = [
	'fields' => [
		'name' => ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'name_abbr' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'seats'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'location'=>['type'=>'object', 'oname'=>'tag_location'],
		'location2'=>['type'=>'varchar(150)','null'=>FALSE, 'default'=>''],
        'ahead_time' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'require_auth' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'description' => ['type'=>'text', 'null'=>TRUE],
		'tag_root' => ['type'=>'object', 'oname'=>'tag'],
	],
	'indexes' => [
		'name'=>['fields'=>['name']],
		'location'=>['fields'=>['location', 'location2']],
	],
];

$config['me_reserv'] = [
	'fields' => [
		'component' => ['type'=>'object', 'oname'=>'cal_component'],
		'meeting' => ['type'=>'object', 'oname' => 'meeting'],
		'user' => ['type'=>'object', 'oname' => 'user'],
		'type' => ['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''],
		'roles' => ['type'=>'text'],
		'groups' => ['type'=>'text'],
		'users' => ['type'=>'text'],
		'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'component' => ['fields' => ['component']],
		'meeting' => ['fields' => ['meeting']],
		'user' => ['fields' => ['user']],
		'dtstart' => ['fields' => ['dtstart']],
		'dtend' => ['fields' => ['dtend']],
	],
];

$config['meeting_announce']=[
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
