<?php

//按照时间进行预约
$config['types']['time'] = [
    'title'=> '时间预约',
    'i18n'=> 'eq_reserv',
    'view'=> 'eq_reserv:edit/reserv_time',
];

//eq_reserv_admin
$config['max_allowed_miss_times'] = 0;
$config['max_allowed_late_times'] = 0;
$config['max_allowed_overtime_times'] = 0;
$config['max_allowed_leave_early_times'] = 0;
$config['max_allowed_violate_times'] = 0;
$config['max_allowed_total_count_times'] = 0;

$config['reserv.user.msg.model'] = [
	'description'=>'用户预约信息更新提示',
	'body'=>'%subject 于 %date 修改 %user 的预约信息',
	'strtr'=>[
			'%subject'=>'更新者',
			'%user'=>'被更新者',
			'%date'=>'时间'
		],

];

$config['reserv.equipment.msg.model'] = [
	'description'=>'仪器预约信息更新提示',
	'body'=>'%subject 于 %date 更新了 %equipment 的预约信息',
	'strtr'=>[
			'%subject'=>'更新者',
			'%equipment'=>'仪器',
			'%date'=>'时间'
		],

];

$config['equipment.reserved.msg'] = [
	'description'=>'仪器被预约的更新提示',
	'body'=>'%subject 于 %date 预约了 %equipment',
	'strtr'=>[
			'%subject'=>'预约者',
			'%equipment'=>'仪器',
			'%date'=>'时间'
		],

];

//添加预约时间单位 ihd
$config['default_add_reserv_limit_format'] = 'i';

//修改预约时间单位 ihd
$config['default_modify_reserv_limit_format'] = 'i';

$config['default_reservlimit_format'] = 'i';
$config['delete_reserv_latest_format'] = 'i';

$config['default_merge_reserv_interval'] = 5;

$config['default_advance_use_time'] = 15;

$config['default_modify_reserv_latest_format'] = 'i';
$config['default_delete_reserv_latest_format'] = 'i';

//关联项目不关联
if ($GLOBALS['preload']['people.multi_lab']) {
	$config['must_connect_lab_project'] = TRUE;
}
else {
	$config['must_connect_lab_project'] = FALSE;
}

$config['use_eq_after_reserv'] = TRUE;

$config['eq_reserv.sortable_columns'] = [
    'equipment',
    'organizer',
    'status',
    'date',
];
// $config['use_eq_captcha'] = TRUE; //是否启用预约验证码

//最小预约块设置，是否允许时间区间交叉,不允许交叉的话表示可以跨天预约块
$config['block_day_cross'] = false;