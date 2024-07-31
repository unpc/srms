<?php

$config['eq_charge']['fields']['voucher'] = ['type' => 'int', 'null' => TRUE, 'default' => 0];
$config['eq_charge']['fields']['auditor'] = ['type' => 'object', 'oname' => 'user']; // 审核者
$config['eq_charge']['fields']['rtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0]; // 审核时间
$config['eq_charge']['fields']['bl_status'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];

$config['eq_charge']['indexes']['auditor'] = ['fields' => ['auditor']];
$config['eq_charge']['indexes']['rtime'] = ['fields' => ['rtime']];
$config['eq_charge']['indexes']['bl_status'] = ['fields'=>['bl_status']];
