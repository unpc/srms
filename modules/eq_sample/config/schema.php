<?php
$config['eq_sample']['fields']['sender'] = ['type'=>'object', 'oname'=>'user'];
$config['eq_sample']['fields']['sender_abbr'] = ['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''];
$config['eq_sample']['fields']['lab'] = ['type' => 'object', 'oname' => 'lab'];
$config['eq_sample']['fields']['equipment'] = ['type'=>'object', 'oname'=>'equipment'];
$config['eq_sample']['fields']['equipment_abbr'] = ['type'=>'varchar(400)', 'null'=>FALSE, 'default'=>''];
$config['eq_sample']['fields']['operator'] = ['type'=>'object', 'oname'=>'user'];
$config['eq_sample']['fields']['operator_abbr'] = ['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''];
$config['eq_sample']['fields']['status'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['eq_sample']['fields']['dtsubmit'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];    //送样时间;
$config['eq_sample']['fields']['dtstart'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];//测样时间起始时间
$config['eq_sample']['fields']['dtend'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];//测样时间结束时间
$config['eq_sample']['fields']['dtpickup'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];//
$config['eq_sample']['fields']['count'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_sample']['fields']['record'] = ['type'=>'object', 'oname'=>'eq_record'];//关联使用记录
$config['eq_sample']['fields']['is_locked'] = ['type'=>'int(1)', 'null'=>FALSE, 'default'=>0];
$config['eq_sample']['fields']['success_samples'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_sample']['fields']['project'] = ['type'=>'object', 'oname'=>'lab_project'];//RQ134732 添加关联项目
$config['eq_sample']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_sample']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['eq_sample']['fields']['preheat'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];// 预热时间
$config['eq_sample']['fields']['cooling'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];// 冷却时间

$config['eq_sample']['indexes']['sender'] = ['fields'=>['sender']];
$config['eq_sample']['indexes']['sender_abbr'] = ['fields'=>['sender_abbr']];
$config['eq_sample']['indexes']['equipment'] = ['fields'=>['equipment']];
$config['eq_sample']['indexes']['equipment_abbr'] = ['fields'=>['equipment_abbr']];
$config['eq_sample']['indexes']['operator'] = ['fields'=>['operator']];
$config['eq_sample']['indexes']['operator_abbr'] = ['fields'=>['operator_abbr']];
$config['eq_sample']['indexes']['status'] = ['fields'=>['status']];
$config['eq_sample']['indexes']['dtsubmit'] = ['fields'=>['dtsubmit']];
$config['eq_sample']['indexes']['dtstart'] = ['fields'=>['dtstart']];
$config['eq_sample']['indexes']['dtend'] = ['fields'=>['dtend']];
$config['eq_sample']['indexes']['dtpickup'] = ['fields'=>['dtpickup']];
$config['eq_sample']['indexes']['record'] = ['fields'=>['record']];
$config['eq_sample']['indexes']['is_locked'] = ['fields'=>['is_locked']];
$config['eq_sample']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['eq_sample']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['eq_sample']['indexes']['preheat'] = ['fields' => ['preheat']];
$config['eq_sample']['indexes']['cooling'] = ['fields' => ['cooling']];
//equipment添加新属性accept_sample:接受送样
$config['equipment']['fields']['accept_sample'] =  ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['equipment']['fields']['sample_require_pc'] =  ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['equipment']['fields']['reserv_require_pc'] =  ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];

$config['eq_sample']['fields']['duty_teacher'] = ['type' => 'object', 'oname'=>'user'];
$config['eq_sample']['indexes']['duty_teacher'] = ['fields' => 'duty_teacher'];