<?php

$config['ac_author'] = [
    'fields'  => [
        'achievement' => ['type' => 'object'],
        'user'        => ['type' => 'object', 'oname' => 'user'],
        'position'    => ['type' => 'int', 'null' => false, 'default' => 0],
        'name'        => ['type' => 'varchar(50)', 'null' => false, 'default' => ''],
        'name_abbr'   => ['type' => 'varchar(50)', 'null' => false, 'default' => ''],
    ],
    'indexes' => [
        'achievement' => ['fields' => ['achievement']],
        'user'        => ['fields' => ['user']],
        'name'        => ['fields' => ['name']],
        'name_abbr'   => ['fields' => ['name_abbr']],
    ],
];

$config['publication'] = [
    'fields'  => [
        'title'        => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],
        'name_abbr'    => ['type' => 'varchar(40)', 'null' => false, 'default' => ''],
        'journal'      => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],
        'journal_abbr' => ['type' => 'varchar(40)', 'null' => false, 'default' => ''],
        'date'         => ['type' => 'int', 'null' => false, 'default' => '0'],
        'volume'       => ['type' => 'int', 'null' => false, 'default' => 0],
        'issue'        => ['type' => 'int', 'null' => false, 'default' => 0],
        'page'         => ['type' => 'varchar(100)', 'null' => false, 'default' => ''],
        'author'       => ['type' => 'text'],
        'content'      => ['type' => 'text'],
        'notes'        => ['type' => 'text'],
        'impact'       => ['type' => 'float', 'default' => '0'],
    ],
    'indexes' => [
        'title'        => ['fields' => ['title']],
        'name_abbr'    => ['fields' => ['name_abbr']],
        'journal'      => ['fields' => ['journal']],
        'journal_abbr' => ['fields' => ['journal_abbr']],
        'date'         => ['fields' => ['date']],
        'volume'       => ['fields' => ['volume']],
        'issue'        => ['fields' => ['issue']],
    ],
];

$config['award'] = [
    'fields'  => [
        'name'        => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],
        'name_abbr'   => ['type' => 'varchar(40)', 'null' => false, 'default' => ''],
        'date'        => ['type' => 'int', 'null' => false, 'default' => '0'],
        'description' => ['type' => 'text'],
        'people'      => ['type' => 'text'],
        'owner'       => ['type' => 'object', 'oname' => 'user'],
    ],
    'indexes' => [
        'name'      => ['fields' => ['name']],
        'name_abbr' => ['fields' => ['name_abbr']],
        'date'      => ['fields' => ['date']],
        'owner'     => ['fields' => ['owner']],
    ],
];

$config['patent'] = [
    'fields'  => [
        'name'      => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],
        'name_abbr' => ['type' => 'varchar(40)', 'null' => false, 'default' => ''],
        'ref_no'    => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],
        'date'      => ['type' => 'int', 'null' => false, 'default' => 0],
        'people'    => ['type' => 'text', 'null' => true],

    ],
    'indexes' => [
        'name'      => ['fields' => ['name']],
        'name_abbr' => ['fields' => ['name_abbr']],
        'date'      => ['fields' => ['date']],
        'ref_no'    => ['fields' => ['ref_no']],
    ],
];
