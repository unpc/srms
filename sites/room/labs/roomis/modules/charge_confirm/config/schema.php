<?php

$config['eq_charge']['fields']['confirm'] = ['type' => 'tinyint', 'null' => FALSE, 'default' => 1]; // 收费确认状态
$config['eq_charge']['fields']['auditor'] = ['type' => 'object', 'oname' => 'user']; // 审核者
$config['eq_charge']['fields']['rtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0]; // 审核时间

$config['eq_charge']['indexes']['confirm'] = ['fields' => ['confirm']];
$config['eq_charge']['indexes']['auditor'] = ['fields' => ['auditor']];
$config['eq_charge']['indexes']['rtime'] = ['fields' => ['rtime']];
