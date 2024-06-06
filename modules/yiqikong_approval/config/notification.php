<?php

$config['approval_create'] = [
	'description' => '设置创建预约审核消息提醒',
	'title' => '预约记录审核提醒',
	'body' => '%incharge：您好：\n\n您负责的仪器%equipment，于%ctime被%user预约，请您在%dtstart前，对预约记录进行审核。若预约到期未审核，此预约记录将被系统自动删除。',
    'i18n_module' => 'approval',
	'strtr' => [
        '%incharge' => '仪器负责人',
		'%equipment' => '预约申请仪器',
		'%ctime' => '预约添加时间',
		'%user' => '预约者',
		'%dtstart' => '预约开始时间'
	],
	'send_by' => [
		'messages' => ['通过消息中心发送', 1],
		'email' => ['通过电子邮件发送', 1],
    ],
	'receive_by' => [
		'email' => true
	]
];

$config['approval_result'] = [
	'description' => '设置预约审核已处理消息提醒',
	'title' => '新的预约审核结果',
	'body' => '您好：\n\n您于%ctime对仪器%equipment的预约申请，被%auditor审核%result。',
    'i18n_module' => 'approval',
	'strtr' => [
		'%ctime' => '预约添加时间',
		'%equipment' => '预约申请仪器',
        '%auditor' => '预约审核人',
        '%flag' => '审核状态'
	],
	'send_by'=>[
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['approval_expired'] = [
	'description' => '设置预约审核已逾期消息提醒',
	'title' => '新的预约审核结果',
	'body' => '您好：\n\n您于%ctime对仪器%equipment的预约申请，由于逾期未审核被系统自动删除。',
    'i18n_module' => 'approval',
	'strtr' => [
        '%ctime' => '预约添加时间',
        '%equipment' => '预约申请仪器'
	],
	'send_by' => [
		'messages' => ['通过消息中心发送', 1],
    ]
];