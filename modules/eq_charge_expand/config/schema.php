<?php

// 仪器收费额外项表格
$config['eq_charge_expand'] = [
	'fields' => [
		// 关联收费
		'charge' => ['type'=>'object', 'oname'=>'eq_charge'],
		// 开机费
		'minimum_fee' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],
		// 机时补贴费
		'subsidy_fee' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],
		// 耗材费
		'expend_fee' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'charge' => ['fields'=>['charge']],
	],
];
