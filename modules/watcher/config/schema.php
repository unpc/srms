<?php

//绑定状态
$config['equipment']['fields']['watcher_code'] = [
    'type' => 'varchar(50)',
    'null' => TRUE,
    'default' => NULL,
];
$config['equipment']['fields']['control_gstation'] = [
    'type' => 'varchar(50)',
    'null' => TRUE,
    'default' => NULL,
];

$config['equipment']['indexes']['watcher_code'] = [
	'type' => 'unique',
	'fields' => ['watcher_code']
];

$config['equipment']['indexes']['control_gstation'] = [
	'fields' => ['control_gstation']
];

$config['eq_client'] = [
	'fields' => [
		'equipment' => ['type' => 'object', 'oname' => 'equipment'],
		'mac_addr' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
		'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
	],
	'indexes' => [
		'equipment' => ['fields' => ['equipment']],
		'mac_addr' => ['fields' => ['mac_addr']],
		'ctime' => ['fields' => ['ctime']],
	],
];