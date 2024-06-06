<?php

$config['eq_evaluate_user'] = [
	'fields' => [
		'equipment' => ['type'=>'object', 'oname' => 'equipment'],
		'user'=>['type'=>'object','oname'=>'user'],
        'status' => ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
        'status_feedback' => ['type'=>'varchar(500)', 'null'=>TRUE],
        'attitude' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],
        'attitude_feedback' => ['type'=>'varchar(500)', 'null'=>TRUE],
        'proficiency' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],
        'proficiency_feedback' => ['type'=>'varchar(500)', 'null'=>TRUE],
        'cleanliness' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],
        'cleanliness_feedback' => ['type'=>'varchar(500)', 'null'=>TRUE],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'equipment'=>['fields'=>['equipment']],
		'user'=>['fields'=>['user']],
        'status'=>['fields'=>['status']],
        'attitude'=>['fields'=>['attitude']],
        'proficiency'=>['fields'=>['proficiency']],
        'cleanliness'=>['fields'=>['cleanliness']],
		'ctime'=>['fields'=>['ctime']]
	],
];

$config['eq_record']['fields']['evaluate_user'] =  ['type'=>'object', 'oname'=>'eq_evaluate_user'];
$config['eq_record']['indexes']['evaluate_user'] = ['fields' => ['evaluate_user']];
