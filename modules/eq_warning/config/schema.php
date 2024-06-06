<?php

$config['eq_warning'] = [
	'fields' => [
		'user'=>['type'=>'object','oname'=>'user'],//设置者
		'unit'=>['type'=>'varchar(24)', 'null'=>FALSE, 'default'=>''],//单位，年、季等
		'unit_value'=>['type'=>'int(11)', 'null'=>FALSE, 'default'=>1],//默认1
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'machine_hour'=>['type'=>'varchar(24)', 'null'=>FALSE, 'default'=>''],//额定机时
		'use_limit_max'=>['type'=>'varchar(24)', 'null'=>FALSE, 'default'=>''],//使用时长
		'use_limit_min'=>['type'=>'varchar(24)', 'null'=>FALSE, 'default'=>''],//使用时长
		'control_tag' => ['type'=>'text'],
		'control_equipment' => ['type'=>'text'],
	],
	'indexes' => [
		'user'=>['fields'=>['user']],
		'ctime'=>['fields'=>['ctime']]
	],
];

$config['eq_warning_rule'] = [
	'fields' => [
		'user'=>['type'=>'object','oname'=>'user'],//最后设置者
		'equipment'=>['type'=>'object','oname'=>'equipment'],//仪器
		'unit'=>['type'=>'varchar(24)', 'null'=>FALSE, 'default'=>''],//单位，年、季等
		'unit_value'=>['type'=>'int(11)', 'null'=>FALSE, 'default'=>1],//默认1
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'machine_hour'=>['type'=>'varchar(24)', 'null'=>FALSE, 'default'=>''],//额定机时
		'use_limit_max'=>['type'=>'varchar(24)', 'null'=>FALSE, 'default'=>''],//使用时长
		'use_limit_min'=>['type'=>'varchar(24)', 'null'=>FALSE, 'default'=>''],//使用时长
	],
	'indexes' => [
		'user'=>['fields'=>['user']],
		'equipment'=>['fields'=>['equipment']],
		'ctime'=>['fields'=>['ctime']]
	],
];
