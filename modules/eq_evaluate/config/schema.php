<?php

$config['eq_evaluate'] = [
	'fields' => [
		'equipment' => ['type'=>'object', 'oname' => 'equipment'],
		'user'=>['type'=>'object','oname'=>'user'],
        'score' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],
        'content' => ['type'=>'varchar(250)', 'null'=>TRUE],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'equipment'=>['fields'=>['equipment']],
		'user'=>['fields'=>['user']],
        'score' => ['fields' => ['score']],
        'content' => ['fields' => ['content']],
		'ctime'=>['fields'=>['ctime']]
	],
];



$config['eq_record']['fields']['evaluate'] =  ['type'=>'object', 'oname'=>'eq_evaluate'];
$config['eq_record']['indexes']['evaluate'] = ['fields' => ['evaluate']];

$config['equipment']['fields']['allow_evaluate'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['equipment']['indexes']['allow_evaluate'] = ['fields' => ['allow_evaluate']];
