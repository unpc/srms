<?php

$config['im_tag'] = [
    'fields' => [
        'name' => ['type' => 'varchar(150)', 'null' => FALSE],
        'name_abbr' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
    ],
    'indexes' => [
        'name' => ['fields' => ['name'], 'type' => 'unique'],
        'ctime' => ['fields' => ['ctime']],
        'mtime' => ['fields' => ['mtime']]
    ]
];

$config['im_record'] = [
    'fields'=> [
        'source' => ['type' => 'object'],
        'user' => ['type' => 'object', 'oname' => 'user'],
        'im_tag' => ['type' => 'object', 'oname' => 'im_tag'],
        'count' => ['type' => 'int', 'null' => FALSE, 'default' => 1],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0]
    ],
    'indexes'=> [
        'source' => ['fields'=>['source']],
        'user' => ['fields'=>['user']],
        'im_tag' => ['fields'=>['im_tag']],
        'count' => ['fields'=>['count']],
        'ctime' => ['fields' => ['ctime']]
    ]
];

$config['eq_record']['fields']['has_impression'] =  ['type' => 'tinyint', 'null' => FALSE, 'default' => 0];

$config['eq_record']['indexes']['has_impression'] =  ['fields' => ['has_impression']];
