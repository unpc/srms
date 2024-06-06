<?php
$config['eq_empower'] = [
	'fields' => [
		/* 工作时间所属仪器 */
		'equipment' => ['type'=>'object', 'oname' => 'equipment'],
		'uncontroluser' => ['type'=>'text', 'null'=>TRUE],
		'uncontrollab' => ['type'=>'text', 'null'=>TRUE],
		'uncontrolgroup' => ['type'=>'text', 'null'=>TRUE],
	],
	'indexes' => [
		'equipment' => ['fields'=>['equipment']],
	],
];

$config['eq_reserv_time'] = [
    'fields' => [
        'equipment' => ['type'=>'object', 'oname' => 'equipment'],
        'meeting' => ['type'=>'object', 'oname' => 'meeting'],
        'ltstart' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'ltend' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'dtstart' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'dtend' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'type' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'num' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'days' => ['type'=>'text', 'null'=>TRUE],
        'controlall' => ['type' => 'tinyint', 'default' => 1, 'null'=>FALSE], // 默认为所有用户都可以使用该工作时间
        'controluser' => ['type'=>'text', 'null'=>TRUE],
        'controllab' => ['type'=>'text', 'null'=>TRUE],
        'controlgroup' => ['type'=>'text', 'null'=>TRUE],
    ],
    'indexes' => [
        'equipment' => ['fields'=>['equipment']],
    ],
];
