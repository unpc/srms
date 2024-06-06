<?php

//默认隐私设置
// 0. All	1. Self Only	2. Lab Members
$config['default_privacy'] = 0;

$config['disable_member_type'] = ['visitors'];

//people_admin
$config['signup.doc'] = '';
$config['signup.title'] = '注册须知';

$config['people_admin.email.content'][] = 'notification.people.add';
$config['people_admin.email.content']['signup'] = 'notification.people.signup';
$config['people_admin.email.content'][] = 'notification.people.activate';

$config['info.msg.model'] = [
	'description'=>'用户基本信息被修改更新提示',
	'body'=>'%subject 于 %date 更新 %user 的基本信息',
	'strtr'=>[
			'%subject'=>'更新者',
			'%user'=>'被更新者',
			'%date'=>'时间'
		],

];
$config['photo.msg.model'] = [
	'description'=>'用户图像被上传更新提示',
	'body'=>'%subject 于 %date 更新了用户 %user 的头像',
	'strtr'=>[
			'%subject'=>'更新者',
			'%user'=>'被更新者',
			'%date'=>'时间'
		],

];
				
$config['export_columns.people'] = [
	'token' => '登录帐号',
	'gender' => '性别',
	'member_type' => '人员类型',
	'mentor_name' => '导师姓名',
	'major' => '专业',
	'organization' => '单位名称',
	'group' => '组织机构',
	'email' => '电子邮箱',
	'phone' => '联系电话',
	'personal_phone' => '个人手机',
	'address' => '地址',
	'lab' => '实验室',
    'lab_contact'=> '实验室联系方式',
	'roles' => '角色',
	];

if (People::perm_in_uno()){
    unset($config['export_columns.people']['member_type']);
    unset($config['export_columns.people']['roles']);
    unset($config['export_columns.people']['gender']);
    unset($config['export_columns.people']['major']);
    unset($config['export_columns.people']['organization']);
    unset($config['export_columns.people']['address']);
    unset($config['export_columns.people']['lab_contact']);
}

/*
// 是否自动关联一卡通(xiaopei.li@2011.08.21)
$config['auto_link_card'] = TRUE;
// 用来查找一卡通的属性
$config['card_link_ref'] = 'token';
// or
// $config['card_link_ref'] = 'ref_no';
// and so on...
*/

/*default configuration: hide mentor_name and personal_phone*/

$config['show_mentor_name'] = FALSE;
$config['show_personal_phone'] = FALSE;
$config['yiqikong_lab_name'] = '仪器控课题组';

$config['reserved.token'] = [
    'admin',
    'root',
    'administrator',
    'superadmin',
];

$config['import_columns.head'] = [
    'A' => '用户姓名*',
    'B' => '登录帐号*',
    'C' => '密码*',
    'D' => '性别',
    'E' => '人员类型*',
    'F' => '学工号',
    'G' => '专业',
    'H' => '单位名称',
    'I' => '组织机构',
    'J' => '电子邮箱*',
    'K' => '联系电话*',
    'L' => '地址',
    'M' => '课题组*',
];

$config['default_group_select_user_group'] = TRUE;


//用户列表默认展示字段(表头)
$config['list_default_show_columns'] = [
    'member_type' => [
        'title' => '人员类型',
        'show' => false
    ],
    'name' => [
        'title' => '姓名',
        'show' => true
    ],
    'contact_info' => [
        'title' => '联系方式',
        'show' => true
    ],
    'group' => [
        'title' => '组织机构',
        'show' => true
    ],
    'address' => [
        'title' => '地址',
        'show' => true
    ],
    'ref_no' => [
        'title' => '学号/工号',
        'show' => false
    ],
    'backends' => [
        'title' => '账户来源',
        'show' => false
    ],
    'creator' => [
        'title' => '建立者',
        'show' => true
    ],
    'auditor' => [
        'title' => '审批者',
        'show' => true
    ],
    'token' => [
        'title' => '登录账号',
        'show' => false
    ],
];

// 列表的搜索不根据系统设置的展示列显隐的字段
$config['search_fields_no_follow_config'] = [
    'group',
    'member_type',
    'name',
    'address',
    'ref_no',
    'backends',
    'email',
    'phone',
    'creator',
    'auditor',
    'token'
];