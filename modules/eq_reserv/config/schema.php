<?php

/* 
	Author: cheng.liu@geneegroup.com 
	Task#6227 CF模块分拆的分支处理 
	* 增加eq_reserv数据表，将reserv信息从record中分离出来
*/
$config['eq_reserv'] = [
	'fields' => [
		/* 预约所属仪器 */
		'equipment' => ['type'=>'object', 'oname' => 'equipment'],
		/* 预约者 */
		'user'=>['type'=>'object','oname'=>'user'],
		/* 关联的预约块 */
		'component'=>['type'=>'object','oname'=>'cal_component'],
		/* 预约状态 */
		'status'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
		/* 预约开始时间 */
		'dtstart'=>['type'=>'int(11)', 'null'=>FALSE, 'default'=>0],
		/* 预约结束时间 */
		'dtend'=>['type'=>'int(11)', 'null'=>FALSE, 'default'=>0],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'equipment'=>['fields'=>['equipment']],
		'user'=>['fields'=>['user']],
		'component'=>['fields'=>['component']],
		'status'=>['fields'=>['status']],
		'dtstart'=>['fields'=>['dtstart']],
		'dtend'=>['fields'=>['dtend']],
		'ctime'=>['fields'=>['ctime']]
	],
];



$config['eq_record']['fields']['reserv'] =  ['type'=>'object', 'oname'=>'eq_reserv'];
$config['eq_record']['indexes']['reserv'] =  ['fields'=>['reserv']];

$config['eq_record']['fields']['flag'] =  ['type'=>'tinyint', 'null'=>FALSE, 'default'=>1];
$config['eq_record']['indexes']['flag'] =  ['fields'=>['flag']];

$config['equipment']['fields']['accept_reserv'] =  ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];  
$config['equipment']['fields']['accept_limit_time'] =  ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['equipment']['fields']['single_equipemnt_reserv'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['equipment']['indexes']['single_equipemnt_reserv'] = ['fields' => ['single_equipemnt_reserv']];

$config['eq_reserv']['fields']['billing_fund'] = ['type' => 'object', 'oname' => 'billing_fund'];
$config['eq_reserv']['indexes']['billing_fund'] = ['fields' => 'billing_fund'];

$config['reserv_log'] = [
	'fields' => [
		'form_token' => ['type'=>'varchar(64)','null'=>FALSE, 'default'=>0],
		'step' => ['type'=>'varchar(64)','null'=>FALSE, 'default'=>0],
		/* 预约者 */
		'user'=>['type'=>'object','oname'=>'user'],
		'equipment'=>['type'=>'object','oname'=>'equipment'],
		/* 预约开始时间 */
		'dtstart'=>['type'=>'int(11)', 'null'=>FALSE, 'default'=>0],
		/* 预约结束时间 */
		'dtend'=>['type'=>'int(11)', 'null'=>FALSE, 'default'=>0],
		'form'=>['type'=>'text', 'null'=>FALSE, 'default'=>''],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'form_token'=>['fields'=>['form_token']],
		'step'=>['fields'=>['step']],
		'equipment'=>['fields'=>['equipment']],
		'user'=>['fields'=>['user']],
		'dtstart'=>['fields'=>['dtstart']],
		'dtend'=>['fields'=>['dtend']],
		'ctime'=>['fields'=>['ctime']]
	],
];

