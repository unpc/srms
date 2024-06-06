<?php
$config['extra'] = [
    'fields'=> [
        'object'=> ['type'=> 'object'],
        'type'=> ['type'=> 'varchar(50)', 'null'=>FALSE, 'default' => ''],
        'params'=> ['type'=> 'json'],
        'ctime'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0],
        'mtime'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0],
        'atime'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0],
        'autoinc'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0]
    ],
    'indexes'=> [
        'type'=> ['fields'=> ['type']],
        'ctime'=> ['fields'=> ['ctime']],
        'mtime'=> ['fields'=> ['mtime']],
        'atime'=> ['fields'=> ['atime']],
        'object'=> ['fields'=> ['object']]
    ]
];
$config['extra_value'] = [
    'fields'=> [
        'object'=> ['type'=> 'object'],
        'values'=> ['type'=>'json'],
    ],
];
