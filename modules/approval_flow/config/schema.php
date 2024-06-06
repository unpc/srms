<?php

$config['approval'] = [
    'fields' => [
        'source' => ['type' => 'object'],
        'user' => ['type' => 'object', 'oname' => 'user'],
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'flag' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'dtstart' => ['type' => 'int(11)', 'null' => FALSE, 'default' => 0],
		'dtend' => ['type' => 'int(11)', 'null' => FALSE, 'default' => 0],
		'dtsubmit' => ['type' => 'int(11)', 'null' => FALSE, 'default' => 0],
		'count' => ['type' => 'int(11)', 'null' => FALSE, 'default' => 0],
        'auto' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
    ],
    'indexes' => [
        'source' => ['fields' => ['source']],
        'user' => ['fields' => ['user']],
        'equipment' => ['fields' => ['equipment']],
        'flag' => ['fields' => ['flag']],
        'dtstart' => ['fields'=>['dtstart']],
		'dtend' => ['fields'=>['dtend']],
        'dtsubmit' => ['fields'=>['dtsubmit']],
        'count' => ['fields'=>['count']],
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

$config['equipment']['fields']['need_approval'] = ['type' => 'tinyint', 'null' => TRUE, 'default' => 1];
$config['equipment']['indexes']['need_approval'] = ['fields' => ['need_approval']];

$config['approved_reject_reserv'] = [
    'fields' => [
        'source' => ['type' => 'object'],
        'project' => ['type' => 'object'],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
    ],
    'indexes' => [
        'source' => ['fields' => ['source']],
        'project' => ['fields' => ['project']],
        'ctime' => ['fields' => ['ctime']]
    ]
];
