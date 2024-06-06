<?php

$config['equipment']['fields'] += [
    'yiqikong_id' => ['type' => 'varchar(100)', 'null'=> TRUE],
    'yiqikong_share' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 1],
    'bluetooth_serial_address' => ['type' => 'varchar(255)', 'null'=> TRUE],
];

$config['equipment']['indexes'] += [
    'yiqikong_id' => ['fields' => ['yiqikong_id'], 'type'=> 'uniqid'],
    'yiqikong_share' => ['fields' => ['yiqikong_share']],
    'bluetooth_serial_address' => ['fields' => ['bluetooth_serial_address']],
];

$config['user']['fields'] += [ 
    'gapper_id' => ['type' => 'int', 'null'=> TRUE],
    'yiqikong_id' => ['type' => 'int', 'null'=> TRUE],
    'outside' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0],
];

$config['user']['indexes'] += [
    'gapper_id' => ['fields'=> ['gapper_id']],
    'yiqikong_id' => ['fields'=> ['yiqikong_id']],
    'outside' => ['fields' => ['outside']],
];

$config['lab']['fields'] += [
    'hidden' => ['type'=> 'tinyint', 'null'=> FALSE, 'default'=> 0,],
];
