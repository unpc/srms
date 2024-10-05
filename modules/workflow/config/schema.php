<?php

$config['workflow'] = [
    'fields' => [
        'source' => ['type' => 'object'],
        'user' => ['type' => 'object', 'oname' => 'user'],
        'flag' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'dtstart' => ['type' => 'int(11)', 'null' => FALSE, 'default' => 0],
		'dtend' => ['type' => 'int(11)', 'null' => FALSE, 'default' => 0],
        'auto' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
    ],
    'indexes' => [
        'source' => ['fields' => ['source']],
        'user' => ['fields' => ['user']],
        'flag' => ['fields' => ['flag']],
        'dtstart' => ['fields'=>['dtstart']],
		'dtend' => ['fields'=>['dtend']],
        'ctime' => ['fields' => ['ctime']]
    ]
];

$config['workflow_node'] = [
    'fields' => [
        'workflow' => ['type' => 'object', 'oname' => 'workflow'],
        'auditor' => ['type' => 'object', 'oname' => 'user'],
        'flag' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'action' => ['type' => 'varchar(50)', 'null' => FALSE, 'default' => ''],
        'dtstart' => ['type' => 'int(11)', 'null' => FALSE, 'default' => 0],
		'dtend' => ['type' => 'int(11)', 'null' => FALSE, 'default' => 0],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
    ],
    'indexes' => [
        'workflow' => ['fields' => ['workflow']],
        'auditor' => ['fields' => ['auditor']],
        'action' => ['fields' => ['action']],
        'flag' => ['fields' => ['flag']],
        'dtstart' => ['fields' => ['dtstart']],
        'dtend' => ['fields' => ['dtend']],
        'ctime' => ['fields' => ['ctime']],
        'unique'=>['type'=>'unique', 'fields'=>['workflow', 'flag']]
    ]
];
