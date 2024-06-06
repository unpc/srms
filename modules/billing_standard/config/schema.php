<?php

$config['eq_charge']['fields']['voucher'] = ['type' => 'int', 'null' => TRUE, 'default' => 0];
$config['eq_charge']['fields']['bl_status'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['indexes']['bl_status'] = ['fields'=>['bl_status']];
$config['eq_charge']['fields']['completetime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['indexes']['completetime'] = ['fields'=>['completetime']];
//报销单号
$config['eq_charge']['fields']['serialnum'] = ['type' => 'varchar(64)', 'default' => ''];
$config['eq_charge']['fields']['vouchernum'] = ['type' => 'varchar(64)', 'default' => ''];

