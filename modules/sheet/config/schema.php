<?php

$config['equipment']['fields']['identity'] = ['type' => 'varchar(64)', 'null' => true, 'default' => null];
$config['equipment']['indexes']['identity'] = ['type'=> 'unique', 'fields' => ['identity']];

$config['eq_record']['fields']['identity'] =  ['type' => 'varchar(64)', 'null' => true, 'default' => null];
$config['eq_record']['indexes']['identity'] =  ['type'=> 'unique', 'fields'=>['identity']];

$config['eq_sample']['fields']['identity'] =  ['type' => 'varchar(64)', 'null' => true, 'default' => null];
$config['eq_sample']['indexes']['identity'] =  ['type'=> 'unique', 'fields'=>['identity']];

$config['eq_reserv']['fields']['identity'] =  ['type' => 'varchar(64)', 'null' => true, 'default' => null];
$config['eq_reserv']['indexes']['identity'] =  ['type'=> 'unique', 'fields'=>['identity']];

$config['user']['fields']['identity'] =  ['type' => 'varchar(64)', 'null' => true, 'default' => null];
$config['user']['indexes']['identity'] =  ['type'=> 'unique', 'fields'=>['identity']];

$config['lab']['fields']['identity'] =  ['type' => 'varchar(64)', 'null' => true, 'default' => null];
$config['lab']['indexes']['identity'] =  ['type'=> 'unique', 'fields'=>['identity']];

$config['cal_component']['fields']['identity'] =  ['type' => 'varchar(64)', 'null' => true, 'default' => null];
$config['cal_component']['indexes']['identity'] =  ['type'=> 'unique', 'fields'=>['identity']];

