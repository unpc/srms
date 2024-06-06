<?php

$config['equipment']['fields']['need_approval'] = ['type' => 'tinyint', 'null' => TRUE, 'default' => 0];

$config['equipment']['indexes']['need_approval'] = ['fields' => ['need_approval']];

$config['eq_reserv']['fields']['approval'] = ['type' => 'tinyint', 'null' => TRUE, 'default' => 0];

$config['eq_reserv']['indexes']['approval'] = ['fields' => ['approval']];

$config['approval'] = [
    'fields' => [
        'source' => ['type' => 'object'],
        'user' => ['type' => 'object', 'oname' => 'user'],
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'flag' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'dtstart' => ['type' => 'int(11)', 'null' => FALSE, 'default' => 0],
		'dtend' => ['type' => 'int(11)', 'null' => FALSE, 'default' => 0],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
    ],
    'indexes' => [
        'source' => ['fields' => ['source']],
        'user' => ['fields' => ['user']],
        'equipment' => ['fields' => ['equipment']],
        'flag' => ['fields' => ['flag']],
        'dtstart' => ['fields'=>['dtstart']],
		'dtend' => ['fields'=>['dtend']],
        'ctime' => ['fields' => ['ctime']]
    ]
];

$config['approved'] = [
    'fields' => [
        'source' => ['type' => 'object'],
        'auditor' => ['type' => 'object', 'oname' => 'user'],
        'flag' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
    ],
    'indexes' => [
        'source' => ['fields' => ['source']],
        'auditor' => ['fields' => ['auditor']],
        'flag' => ['fields' => ['flag']],
        'ctime' => ['fields' => ['ctime']]
    ]
];

$config['yiqikong_approval_uncontrol'] = [
    'fields' => [
        'equipment' => ['type'=>'object', 'oname' => 'equipment'],
        'approval_type' => ['type'=>'varchar(10)', 'null' => FALSE, 'default' => 'eq_reserv'],
        'uncontroluser' => ['type'=>'text', 'null'=>TRUE],
        'uncontrollab' => ['type'=>'text', 'null'=>TRUE],
        'uncontrolgroup' => ['type'=>'text', 'null'=>TRUE],
    ],
    'indexes' => [
        'equipment' => ['fields'=>['equipment','approval_type']],
    ],
];
