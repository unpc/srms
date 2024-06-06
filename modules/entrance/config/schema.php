<?php

$config['door']['fields']['location1']   = ['type' => 'varchar(150)', 'null' => false, 'default' => ''];
$config['door']['fields']['location2']   = ['type' => 'varchar(50)', 'null' => false, 'default' => ''];
$config['door']['fields']['name']        = ['type' => 'varchar(50)', 'null' => false, 'default' => ''];
$config['door']['fields']['in_addr']     = ['type' => 'varchar(50)', 'null' => false, 'default' => ''];
$config['door']['fields']['out_addr']    = ['type' => 'varchar(50)', 'null' => false, 'default' => ''];
$config['door']['fields']['lock_id']     = ['type' => 'varchar(50)', 'null' => false, 'default' => ''];
$config['door']['fields']['detector_id'] = ['type' => 'varchar(50)', 'null' => false, 'default' => ''];
$config['door']['fields']['is_open']     = ['type' => 'tinyint', 'null' => false, 'default' => 1];
$config['door']['fields']['server']      = ['type' => 'varchar(255)', 'null' => false, 'default' => ''];
$config['door']['fields']['ctime']       = ['type' => 'int', 'null' => false, 'default' => 0];
$config['door']['fields']['mtime']       = ['type' => 'int', 'null' => false, 'default' => 0];
$config['door']['fields']['type']        = ['type' => 'tinyint', 'null' => false, 'default' => 1];
$config['door']['fields']['location']    = ['type' => 'object', 'oname' => 'tag_location'];
$config['door']['fields']['remote_device']    = ['type' => 'object', 'oname' => 'door_device'];
$config['door']['fields']['remote_device2']    = ['type' => 'object', 'oname' => 'door_device'];
$config['door']['fields']['voucher']      = ['type' => 'int', 'null' => false, 'default' => 0];

$config['door']['indexes']['name']  = ['fields' => ['name']];
$config['door']['indexes']['ctime'] = ['fields' => ['ctime']];
$config['door']['indexes']['mtime'] = ['fields' => ['mtime']];
$config['door']['indexes']['type'] = ['fields' => ['type']];
$config['door']['indexes']['location'] = ['fields' => ['location']];
$config['door']['indexes']['remote_device'] = ['fields' => ['remote_device']];
$config['door']['indexes']['remote_device2'] = ['fields' => ['remote_device2']];

$config['dc_record'] = [
    'fields'  => [
        'door'      => ['type' => 'object', 'oname' => 'door'],
        'user'      => ['type' => 'object', 'oname' => 'user'],
        'time'      => ['type' => 'int', 'null' => false, 'default' => 0],
        'direction' => ['type' => 'int', 'null' => false, 'default' => 0],
        'status'    => ['type' => 'tinyint', 'null' => false, 'default' => 1],
        'voucher'   => ['type' => 'int', 'null' => false, 'default' => 0]
    ],
    'indexes' => [
        'unique' => ['fields' => ['door', 'user', 'time', 'direction']],
        'door'   => ['fields' => ['door']],
        'user'   => ['fields' => ['user']],
        'time'   => ['fields' => ['time']],
        'status' => ['fields' => ['status']],
        'voucher' => ['fields' => ['voucher']],
    ],
];

$config['door_device'] = [
    'fields'  => [
        'name'        => ['type' => 'varchar(50)', 'null' => false, 'default' => ''],
        'uuid'        => ['type' => 'varchar(200)', 'null' => false, 'default' => '']
    ],
    'indexes' => [
        'name'   => ['fields' => ['name']],
        'uuid'   => ['fields' => ['uuid']],
    ],
];