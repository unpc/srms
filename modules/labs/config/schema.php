<?php

$config['lab_project'] = [
	'fields' => [
		'lab'=>['type'=>'object', 'oname'=>'lab'],
		'name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'type'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtstart'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'status'=>['type'=>'int', 'null'=>FALSE, 'default'=>0]
	],
	'indexes'=>[
		'name'=>['type'=>'unique','fields'=>['lab', 'name', 'type']],
		'type'=>['fields'=>['type']],
		'lab'=>['fields'=>['lab']],
		'dtstart'=>['fields'=>['dtstart']],
		'dtend'=>['fields'=>['dtend']],
		'status'=>['fields'=>['status']]
	],
];

//关联项目
$config['eq_reserv']['fields']['project'] = ['type' => 'object', 'oname' => 'lab_project'];
$config['eq_sample']['fields']['project'] = ['type' => 'object', 'oname' => 'lab_project'];
$config['eq_record']['fields']['project'] = ['type' => 'object', 'oname' => 'lab_project'];
