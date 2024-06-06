<?php

$config['gapper_user'] = [
    'fields' => [
        'gapper_id' => ['type' => 'varchar(40)', 'default' => ''],
        'name' => ['type' => 'varchar(40)', 'default' => ''],
        'email' => ['type' => 'varchar(128)', 'default' => ''],
        'ref_no' => ['type' => 'varchar(128)', 'default' => ''],
        'avatar' => ['type' => 'varchar(240)', 'default' => ''],
    ],
    'indexes' => [
        'ref_no' => ['fields' => ['ref_no']],
        'email' => ['fields' => ['email']],
    ],
];

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['lab']['fields']['gapper_id'] = ['type' => 'int', 'null' => FALSE];
    $config['lab']['indexes']['gapper_id'] = ['fields' => ['gapper_id']];

    $config['tag_group']['fields']['type'] = ['type' => 'varchar(50)', 'null' => FALSE,'default'=>''];
    $config['tag_group']['indexes']['type'] = ['fields' => ['type']];
    $config['tag_group']['fields']['gapper_id'] = ['type' => 'int', 'null' => FALSE,'default'=>0];
    $config['tag_group']['indexes']['gapper_id'] = ['fields' => ['gapper_id']];

    $config['role']['fields']['gapper_id'] = ['type' => 'int', 'null' => FALSE];
    $config['role']['indexes']['gapper_id'] = ['fields' => ['gapper_id']];
    $config['role']['fields']['type'] = ['type' => 'varchar(50)', 'null' => FALSE];
    $config['role']['indexes']['type'] = ['fields' => ['type']];

    $config['perm']['fields']['gapper_key'] = ['type' => 'varchar(100)', 'null' => FALSE]; // gapper 权限标识(无id所以用key)
    $config['perm']['indexes']['gapper_key'] = ['fields' => ['gapper_key']];
}

$config['gapper_groups'] = [
    'fields' => [
        'gapper_id' => ['type' => 'varchar(40)', 'default' => ''],
        'name' => ['type' => 'varchar(40)', 'default' => ''],
        'type' => ['type' => 'varchar(128)', 'default' => ''],
    ],
    'indexes' => [
        'gapper_id' => ['fields' => ['gapper_id']],
        'name' => ['fields' => ['name']],
    ],
];

$config['tag_location']['fields']['gapper_id'] = ['type' => 'varchar(64)', 'null' => FALSE,'default'=>''];
$config['tag_location']['indexes']['gapper_id'] = ['fields' => ['gapper_id']];

$config['user']['fields']['history_gapperid'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['user']['indexes']['history_gapperid'] = ['fields' => ['history_gapperid']];