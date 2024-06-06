<?php

// $config['classification']['user']["reserv_approve\004仪器送样的相关消息提醒"][] = 'reserv_approve.add_sample.sender';

$config['reserv_approve.to_approvers.sender'] = [
	'description'=>'设置预约审核待处理消息提醒',
	'title'=>'新的预约审核待处理',
	'body'=>'您好:\n\n%user于%time对仪器%eq_name进行预约申请.需要您处理%approve_url，如果此条待审核记录消失，说明其他人员已审核',
    'i18n_module'=>'reserv_approve',
	'strtr'=>[
        '%user'=> '预约审核发起人',
        '%time'=> '预约添加时间',
        '%eq_name'=> '预约申请仪器',
        '%approve_url' => '审批地址'
	],
	'send_by'=>[
		// 'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['reserv_approve.to_user.sender'] = [
	'description'=>'设置预约审核已处理消息提醒',
	'title'=>'新的预约审核进展',
	'body'=>'您好:\n\n您于%time对仪器%eq_name的预约申请.被%approver审核%state.详情见%approve_url',
    'i18n_module'=>'reserv_approve',
	'strtr'=>[
        '%time'=> '预约添加时间',
        '%eq_name'=> '预约申请仪器',
        '%approver'=> '预约审核人',
        '%state'=> '通过/驳回',
        '%approve_url' => '审批地址'
	],
	'send_by'=>[
		// 'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['reserv_approve_overdue.to_user.sender'] = [
	'description'=>'设置预约审核已逾期消息提醒',
	'title'=>'新的预约审核进展',
	'body'=>'您好:\n\n您于%time对仪器%eq_name的预约申请.由于逾期未审核被系统自动删除.详情见%approve_url',
    'i18n_module'=>'reserv_approve',
	'strtr'=>[
        '%time'=> '预约添加时间',
        '%eq_name'=> '预约申请仪器',
        '%approve_url' => '审批地址'
	],
	'send_by'=>[
		// 'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['reserv_approve_success.to_incharge.sender'] = [
	'description'=>'预约审核通过机主提醒',
	'title'=>'新的预约审核通过',
	'body'=>'您好:\n\n%incharge于%time对仪器%eq_name的预约(%dur)审核通过.详情见%reserv_url',
    'i18n_module'=>'reserv_approve',
	'strtr'=>[
		'%incharge' => '审批人',
        '%time'=> '审批通过时间',
		'%eq_name'=> '预约申请仪器',
		'%dur'=> '预约时段',
        '%reserv_url' => '预约地址'
	],
	'send_by'=>[
		// 'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['reserv_approve_change.to_user.sender'] = [
	'description'=>'设置预约审核者改变消息提醒',
	'title'=>'新的预约审核进展',
	'body'=>'您好:\n\n您于%time对仪器%eq_name的预约申请.由于审核者被修改，需删除预约并重新提交申请.详情见%approve_url',
    'i18n_module'=>'reserv_approve',
	'strtr'=>[
        '%time'=> '预约添加时间',
        '%eq_name'=> '预约申请仪器',
        '%approve_url' => '审批地址'
	],
	'send_by'=>[
		// 'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];