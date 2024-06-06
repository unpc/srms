<?php

$config['eq_banned'] = [
	'fields' => [
		# 对应封禁对象(仪器、组织机构、空为全局)
		'object'=>['type'=>'object'],
		'user'=>['type'=>'object', 'oname'=>'user'],
		'obj_abbr'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'lab'=>['type'=>'object', 'oname'=>'lab'],
		# 封禁原因
		'reason'=>['type'=>'text', 'null'=>TRUE],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'atime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'unique'=>['fields'=>['object', 'user', 'lab'], 'type'=>'unique'],
		'obj_abbr'=>['fields'=>['obj_abbr']],
		'ctime'=>['fields'=>['ctime']],
		'atime'=>['fields'=>['atime']],
	],
];

$config['user_violation'] = [
	'fields' => [
		'user'=>['type'=>'object', 'oname'=>'user'],
		'total_count'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'eq_miss_count'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'eq_leave_early_count'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'eq_overtime_count'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'eq_late_count'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'eq_violate_count'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'unique'=>['fields'=>['user'], 'type'=>'unique'],
		'total_count'=>['fields'=>['total_count']],
		'eq_miss_count'=>['fields'=>['eq_miss_count']],
		'eq_leave_early_count'=>['fields'=>['eq_leave_early_count']],
		'eq_overtime_count'=>['fields'=>['eq_overtime_count']],
		'eq_late_count'=>['fields'=>['eq_late_count']],
		'eq_violate_count'=>['fields'=>['eq_violate_count']],
		'ctime'=>['fields'=>['ctime']],
	],
];

$config['user_violation_record'] = [
	'fields' => [
		'user'=>['type'=>'object', 'oname'=>'user'],
		'equipment'=>['type'=>'object', 'oname'=>'equipment'],
		'reason'=>['type'=>'text', 'null'=>TRUE],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'ctime'=>['fields'=>['ctime']],
	],
];

//历史记录
$config['eq_banned_record'] = [
    'fields' => [
        # 对应封禁对象(仪器、组织机构、空为全局)
        'object'=>['type'=>'object'],
        'user'=>['type'=>'object', 'oname'=>'user'],
        'obj_abbr'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
        'lab'=>['type'=>'object', 'oname'=>'lab'],
        # 封禁原因
        'reason'=>['type'=>'text', 'null'=>TRUE],
        'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'atime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'unsealing_user'=>['type'=>'object', 'oname'=>'user'],
        'unsealing_ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes' => [
        'unique'=>['fields'=>['object', 'user', 'lab','ctime'], 'type'=>'unique'],
        'obj_abbr'=>['fields'=>['obj_abbr']],
        'atime'=>['fields'=>['atime']],
    ],
];
