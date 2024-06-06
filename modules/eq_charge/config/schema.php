<?php

$config['eq_charge']['fields']['user'] = ['type'=>'object', 'oname'=>'user'];
$config['eq_charge']['fields']['lab'] = ['type'=>'object', 'oname'=>'lab'];
$config['eq_charge']['fields']['equipment'] = ['type'=>'object', 'oname'=>'equipment'];
$config['eq_charge']['fields']['status'] = ['type' => 'int', 'null' => TRUE, 'default' => 0];
$config['eq_charge']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['fields']['dtstart'] = ['type' => 'int(11)', 'null' => TRUE, 'default' =>0];
$config['eq_charge']['fields']['dtend'] = ['type' => 'int(11)', 'null' => TRUE, 'default' =>0];
$config['eq_charge']['fields']['auto_amount'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['fields']['amount'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['fields']['custom'] = ['type'=>'int(1)', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['fields']['charge_template'] = ['type' => 'varchar(100)', 'null' => FALSE, 'default' => ''];
$config['eq_charge']['fields']['charge_type'] = ['type' => 'varchar(50)', 'null' => FALSE, 'default' => ''];
$config['eq_charge']['fields']['transaction'] = ['type'=>'object', 'oname'=>'billing_transaction'];
$config['eq_charge']['fields']['is_locked'] = ['type'=>'int(1)', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['fields']['source'] = ['type' => 'object'];
$config['eq_charge']['fields']['description'] = ['type' => 'text', 'null' => TRUE];
$config['eq_charge']['fields']['serialcode'] = ['type' => 'text', 'null' => TRUE];


$config['eq_charge']['indexes']['lab'] = ['fields'=>['lab']];
$config['eq_charge']['indexes']['user'] = ['fields'=>['user']];
$config['eq_charge']['indexes']['equipment'] = ['fields'=>['equipment']];
$config['eq_charge']['indexed']['status'] = ['fields' => ['status']];
$config['eq_charge']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['eq_charge']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['eq_charge']['indexes']['dtstart'] = ['fields'=>['dtstart']];
$config['eq_charge']['indexes']['dtend'] = ['fields'=>['dtend']];
$config['eq_charge']['indexes']['transaction'] = ['fields'=>['transaction']];
$config['eq_charge']['indexes']['is_locked'] = ['fields'=>['is_locked']];
$config['eq_charge']['indexes']['source'] = ['fields'=>['source']];

$config['eq_charge']['indexes']['charge_unique'] = ['type' => 'unique', 'fields' => ['source', 'user', 'charge_type']];

//关于仪器财务部门的声明应该放在eq_charge里面 而不是equipments里面
//那你为嘛不放billing里面呢
$config['equipment']['fields']['billing_dept'] = ['type'=>'object', 'oname'=>'billing_department'];
$config['equipment']['indexes']['billing_dept'] = ['fields'=>'billing_dept'];