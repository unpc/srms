<?php

//equipment_admin
$config['max_overtime_duration'] = 0;
$config['max_allowed_overtime_times'] = 0;
$config['modify_time_limit'] = 0;
//总体临时实验室名称
$config['temp_lab_name'] = '仪器使用临时实验室';
$config['default_empty_message'] = '无符合条件的仪器';
$config['capture_font'] = 'capture.ttf';
/*
NO.TASK#214(朱洪杰@2010.11.08)
定期更新离线密码的设置
*/
//$config['offline_password_lifetime'] = 86400; //以秒为单位
$config['free_access_users'] = [];
$config['access_code_lifetime'] = 900;	//access_code 15min内过期

$config['control_modes'] = [
    'nocontrol' => '不控制',
    'power' => '电源控制',
    'computer' => '电脑登录',
    'veronica' => '新版电脑客户端',
    'ultron' => '终端控制',
    'bluetooth' => '蓝牙控制',
];

$config['info.msg.model'] = [
	'description'=>'设置仪器基本信息修改之后的更新提示',
	'body'=>'%subject 于 %date 更新了 %equipment 的基本信息',
	'strtr'=>[
		'%subject'=>'修改者',
		'%equipment'=>'仪器名称',
		'%date'=>'时间'
	],
];

$config['photo.msg.model'] = [
	'description'=>'设置仪器图像修改之后的更新提示',
	'body'=>'%subject 于 %date 更新了仪器 %equipment 的设备图标',
	'strtr'=>[
		'%subject'=>'修改者',
		'%equipment'=>'仪器名称',
		'%date'=>'时间'
	],
];

$config['status.msg.model'] = [
	'description'=>'设置仪器状态修改之后的更新提示',
	'body'=>'%subject 于 %date 修改了 %equipment 的状态',
	'strtr'=>[
		'%subject'=>'修改者',
		'%equipment'=>'仪器名称',
		'%date'=>'时间'
	],
];

$config['use.msg.model'] = [
	'description'=>'设置仪器使用信息修改的更新提示',
	'body'=>'%subject 于 %date 修改了 %equipment 的使用信息',
	'strtr'=>[
		'%subject'=>'预约者',
		'%equipment'=>'仪器名称',
		'%date'=>'时间'
	],
];

//仪器按组织机构计费
$config['enable_group_specs'] = TRUE;
/*仪器负责人人数上线数字，0为无限*/
$config['max_incharges'] = 0;
//是否显示仪器总数
$config['total_count'] = true;
//发送离线密码相关的配置
$config['offline_password_receivers'] = ['support@geneegroup.com'];
$config['super_key'] = 'genee123';

//默认配置为 ‘default’,如果用户没有设置电脑控制方式的视频地址，系统选择默认配置
$config['default_capture_stream_name'] = 'inner';
$config['capture_stream_to'] = [];

// $config['feedback_deadline'] = 1;

$config['record.print_max'] = 500;

$config['domain'] = [
'A' => '电子信息', 
'B' => '生物制药', 
'C' => '新材料', 
'D' => '先进制造', 
'E' => '现代农业', 
'F' => '新能源', 
'G' => '环境保护', 
'H' => '现代交通',
'I' => '城市建设与社会发展', 
'J' => '市民生活', 
'K' => '文化创意', 
'L' => '食品安全', 
'M' => '其他',
];

$config['enable_use_type'] = FALSE;
$config['qrcode.color'] = '#2BA07F';

$config['teach_information_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/lims/!equipments/teach_download/index.';

$config['force_read'] =  false;

$config['holiday_support'] =  false;//开启假期设置

$config['enable_use_type_list'] = [
    EQ_Record_Model::USE_TYPE_USING
];

$config['location_type_select'] = TRUE;

$config['feedback_samples_allow_zero'] = true;

// 列表的搜索不根据系统设置的展示列显隐的字段
$config['search_fields_no_follow_config'] = [
    'ref_no',
    'name',
    'group',
    'location',
    'control',
    'contact',
    'atime',
];