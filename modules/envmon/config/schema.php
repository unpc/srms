<?php

$config['env_node']['fields']['name'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['env_node']['fields']['location'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['env_node']['fields']['location2'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['env_node']['fields']['alarm'] = ['type' => 'tinyint', 'null' => FALSE, 'default' => 0];
$config['env_node']['fields']['ctime'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['env_node']['fields']['mtime'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];

$config['env_node']['indexes']['location'] = ['fields' => ['location']];
$config['env_node']['indexes']['location2'] = ['fields' => ['location2']];
$config['env_node']['indexes']['alarm'] = ['fields' => ['alarm']];
$config['env_node']['indexes']['name'] = ['fields' => ['name']];

$config['env_sensor'] = [
	'fields' => [
		'name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'node'=>['type'=>'object', 'oname'=>'env_node'],
        'type'=>['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''],
		'value' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],
        'interval'=>['type'=>'int', 'null'=>FALSE, 'default'=>30],
		'vfrom'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
		'vto'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
		
		'data_alarm'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],

		//是否设置异常报警
		'abnormal_check_status'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
		//数据异常多长时间报警
		'alert_time'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		//每隔多长时间检测一次
		'check_abnormal_time'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		//最多连续报警几次
		'limit_abnormal_times'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		
		//是否设置无数据报警
		'nodata_check_status'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
		//无数据多长时间报警
		'nodata_alert_time'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		//每隔多长时间检测一次
		'check_nodata_time'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		//最多连续报警几次
		'limit_nodata_times'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	
		
		
		'address'=> ['type'=>'varchar(80)', 'null'=>TRUE, 'default'=>NULL],
        'status'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0]
	],
	'indexes' => [
		'node' => ['fields'=>['node']],
		'address' => ['fields'=>['address'], 'type'=>'unique'],
		'ctime' => ['fields'=>['ctime']],
	],
];

$config['env_sensor_alarm'] = [
	'fields' => [
		'sensor' => ['type'=>'object', 'oname'=>'env_sensor'],
		'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		],
	'indexes' => [
		'sensor' => ['fields'=>['sensor']],
		'dtstart' => ['fields'=>['dtstart']],
		'dtend' => ['fields'=>['dtend']],
		],
];

$config['env_datapoint'] = [
	'fields' => [
		'sensor' => ['type'=>'object', 'oname'=>'env_sensor'],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'value' => ['type'=>'double', 'null'=>FALSE, 'default'=>0]
	],
	'indexes' => [
		'sensor' => ['fields'=>['sensor']],
		'ctime' => ['fields'=>['ctime']],
		'value' => ['fields'=>['value']]
	]
];

$config['env_actual_datapoint'] = [
	'fields' => [
		'sensor' => ['type'=>'object', 'oname'=>'env_sensor'],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'exp_time' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'value' => ['type'=>'double', 'null'=>FALSE, 'default'=>0]
	],
	'indexes' => [
		'sensor' => ['fields'=>['sensor']],
		'ctime' => ['fields'=>['ctime']],
		'exp_time' => ['fields'=>['exp_time']],
		'value' => ['fields'=>['value']]
	]
];
