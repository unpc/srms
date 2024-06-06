<?php

$config['eq_charge']['fields']['remoteLock'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['indexes']['remoteLock'] = ['fields'=>['remoteLock']];
// 报销状态
$config['eq_charge']['fields']['bl_status'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['fields']['serialcode'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_charge']['indexes']['bl_status'] = ['fields'=>['bl_status']];
$config['lab_project']['fields']['card'] = ['type'=>'varchar(50)', 'null'=>FALSE, 'default'=>''];
$config['lab_project']['indexes']['card'] = ['fields'=>['card']];
