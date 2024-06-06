<?php
$config['resume'] = [
	'fields' => [
		'uname' => ['type' => 'varchar(50)', 'null' => FALSE, 'default' => ''],
		'uname_abbr' => ['type' => 'varchar(50)', 'null' => FALSE, 'default' => ''],
		'status' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'phone' => ['type' => 'varchar(50)', 'null' => TRUE, 'default' => ''],
		'sex' => ['type' => 'tinyint', 'null' => TRUE, 'default' => 0],
		'birthday' => ['type' => 'int', 'null' => TRUE, 'default' => 0],
		'education' => ['type' => 'tinyint', 'null' => TRUE, 'default' => 0],
		'school' => ['type' => 'varchar(50)', 'null' => TRUE, 'default' => ''],
		'position' => ['type' => 'object', 'oname' => 'position'],
		'interview_place' => ['type' => 'tinyint', 'null' => TRUE, 'default' => 0],
		'current_location' => ['type' => 'varchar(50)', 'null' => TRUE, 'default' => ''],
		'experience' => ['type' => 'text', 'null' => TRUE, 'default' => ''],
		'education_background' => ['type' => 'text', 'null' => TRUE, 'default' => ''],
		'interview_time' => ['type' => 'int', 'null' => TRUE, 'default' => 0],
		'description' => ['type' => 'text', 'null' => TRUE, 'default' => ''],
		'opinion' => ['type' => 'varchar(150)', 'null' => TRUE, 'default' => ''],
		'feedback' => ['type' => 'varchar(150)', 'null' => TRUE, 'default' => ''],
		'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
		],
	'indexes' => [
		'uname' => ['fields' => ['uname']],
		'uname_abbr' => ['fields' => ['uname_abbr']],
		'status' => ['fields' => ['status']],
		'phone' => ['fields' => ['phone']],
		'sex' => ['fields' => ['sex']],
		'birthday' => ['fields' => ['birthday']],
		'education' => ['fields' => ['education']],
		'school' => ['fields' => ['school']],
		'position' => ['fields' => ['position']],
		'interview_place' => ['fields' => ['interview_place']],
		'current_location' => ['fields' => ['current_location']],
		'interview_time' => ['fields' => ['interview_time']],
		'ctime' => ['fields' => ['ctime']]
		]];

$config['position'] = [
	'fields' => [
		'name' => ['type' => 'varchar(50)', 'null' => FALSE, 'default' => ''],
		'department' => ['type' => 'varchar(50)', 'null' => TRUE, 'default' => ''],
		'minsalary' => ['type' => 'int', 'null' => TRUE, 'default' => 0],
		'maxsalary' => ['type' => 'int', 'null' => TRUE, 'default' => 0],
		'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
		'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
		],
	'indexes' => [
		'name' => ['type' => 'unique', 'fields' => ['name']],
		'department' => ['fields' =>['department']],
		'minsalary' => ['fields' => ['minsalary']],
		'maxsalary' => ['fields' => ['maxsalary']]
		]];
