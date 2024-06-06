<?php

$config['lab']['fields']['uuid'] = ['type' => 'varchar(100)', 'null'=> true];
$config['lab']['fields']['platform'] = ['type' => 'varchar(100)', 'null'=> true];
$config['lab']['fields']['version'] = ['type' => 'int', 'null'=> false, 'default' => 0];
$config['lab']['indexes']['uuid'] = ['fields' => ['uuid'], 'type'=> 'uniqid'];
$config['lab']['indexes']['version'] = ['fields' => ['version']];

$config['user']['fields']['uuid'] = ['type' => 'varchar(100)', 'null'=> true];
$config['user']['fields']['platform'] = ['type' => 'varchar(100)', 'null'=> true];
$config['user']['fields']['version'] = ['type' => 'int', 'null'=> false, 'default' => 0];
$config['user']['indexes']['uuid'] = ['fields' => ['uuid'], 'type'=> 'uniqid'];
$config['user']['indexes']['version'] = ['fields' => ['version']];

$config['tag']['fields']['uuid'] = ['type' => 'varchar(100)', 'null'=> true];
$config['tag']['fields']['platform'] = ['type' => 'varchar(100)', 'null'=> true];
$config['tag']['fields']['version'] = ['type' => 'int', 'null'=> false, 'default' => 0];
$config['tag']['indexes']['uuid'] = ['fields' => ['uuid'], 'type'=> 'uniqid'];
$config['tag']['indexes']['version'] = ['fields' => ['version']];

$config['equipment']['fields']['uuid'] = ['type' => 'varchar(100)', 'null'=> true];
$config['equipment']['fields']['platform'] = ['type' => 'varchar(100)', 'null'=> true];
$config['equipment']['fields']['version'] = ['type' => 'int', 'null'=> false, 'default' => 0];
$config['equipment']['indexes']['uuid'] = ['fields' => ['uuid'], 'type'=> 'uniqid'];
$config['equipment']['indexes']['version'] = ['fields' => ['version']];


$config['role']['fields']['uuid'] = ['type' => 'varchar(100)', 'null'=> true];
$config['role']['fields']['platform'] = ['type' => 'varchar(100)', 'null'=> true];
$config['role']['fields']['version'] = ['type' => 'int', 'null'=> false, 'default' => 0];
$config['role']['indexes']['uuid'] = ['fields' => ['uuid'], 'type'=> 'uniqid'];
$config['role']['indexes']['version'] = ['fields' => ['version']];

$config['perm']['fields']['uuid'] = ['type' => 'varchar(100)', 'null'=> true];
$config['perm']['fields']['platform'] = ['type' => 'varchar(100)', 'null'=> true];
$config['perm']['fields']['version'] = ['type' => 'int', 'null'=> false, 'default' => 0];
$config['perm']['indexes']['uuid'] = ['fields' => ['uuid'], 'type'=> 'uniqid'];
$config['perm']['indexes']['version'] = ['fields' => ['version']];
