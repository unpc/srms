<?php

$config['eq_current_dp'] = [
	'fields' => [
		'equipment' => ['type'=>'object', 'oname'=>'equipment'],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'value' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'prev' => ['type'=>'object', 'oname'=>'eq_current_dp'],
	],
	'indexes' => [
		'equipment' => ['fields'=>['equipment']],
		'ctime' => ['fields'=>['ctime']],
		'value' => ['fields'=>['value']]
	]
];

$config['eq_power_time'] = [
	'fields' => [
		'equipment' => ['type'=>'object', 'oname'=>'equipment'],
		'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'duration' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'equipment' => ['fields'=>['equipment']],
		'dtstart' => ['fields'=>['dtstart']],
		'dtend' => ['fields'=>['dtend']],
		'ctime' => ['fields'=>['ctime']],
		'mtime' => ['fields'=>['mtime']],
	]
];

