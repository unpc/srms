<?php

$config['research'] = [
    'fields'  => [
        'ref_no'   => ['type' => 'varchar(150)', 'null' => true],
        'name'     => ['type' => 'varchar(150)', 'null' => true],
        'group'    => ['type' => 'object', 'oname' => 'tag'],
        'content'  => ['type' => 'varchar(500)', 'null' => true],
        'charge'   => ['type' => 'varchar(500)', 'null' => true],
        'location' => ['type' => 'varchar(500)', 'null' => true],
        'phone'    => ['type' => 'varchar(150)', 'null' => true],
        'email'    => ['type' => 'varchar(150)', 'null' => true],
        'ctime'    => ['type' => 'int', 'null' => FALSE, 'default' => 0]
    ],
    'indexes' => [
        'ref_no' => ['fields' => ['ref_no']],
        'name'   => ['fields' => ['name']],
        'group'  => ['fields' => ['group']],
        'ctime'  => ['fields' => ['ctime']],
    ],
];

$config['research_record'] = [
    'fields'  => [
        'research'      => ['type' => 'object', 'oname' => 'research'],
        'user'          => ['type' => 'object', 'oname' => 'user'],
        'research_no'   => ['type' => 'varchar(32)', 'null' => false],
        'price'         => ['type' => 'double', 'null' => false, 'default' => 0],
        'quantity'      => ['type' => 'varchar(500)', 'null' => true],
        'amount'        => ['type' => 'double', 'null' => false, 'default' => 0],
        'discount'      => ['type' => 'double', 'null' => false, 'default' => 0],
        'auto_amount'   => ['type' => 'double', 'null' => false, 'default' => 0],
        // 'date' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'dtstart'       => ['type' => 'int', 'null' => false, 'default' => 0],
        'dtend'         => ['type' => 'int', 'null' => false, 'default' => 0],
        'charge_status' => ['type' => 'tinyint', 'null' => false, 'default' => 0],
        'description'   => ['type' => 'text', 'null' => true],
    ],
    'indexes' => [
        'research'      => ['fields' => ['research']],
        'research_no'   => ['fields' => ['research_no']],
        'user'          => ['fields' => ['user']],
        // 'date'          => ['fields' => ['date']],
        'charge_status' => ['fields' => ['charge_status']],
    ],
];
