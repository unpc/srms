<?php

$config['labs.register'] = [
	'description' => '用户激活时实验室PI的消息提醒',
	'title' => '提醒: 成员帐号被激活',
	'body' => '%PI, 您好! \n在贵单位正在使用的大型仪器共享管理系统中, 您实验室%lab内的%user个人帐号已经被激活. \n祝您: 工作顺利, 心情愉快! \n\n',
	'i18n_module' => 'labs',
	'strtr' => [
		'%user' => '被激活账号',
		'%PI'   => '实验室PI',
		'%lab'  => '实验室',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_charge.charge_need_approve'] = [
	'description' => '使用费用超标提醒',
	'title' => '提醒: 您实验室的成员超额使用了仪器',
	'body' => '%pi, 您好! \n您实验室中的%user在%time通过大型仪器共享管理系统使用了%equipment, 该次仪器使用所产生的费用超出了您设置标准, 特此提醒! \n祝您: 工作顺利, 心情愉快! \n\n',
	'i18n_module' => 'eq_charge',
	'strtr' => [
            '%time'=>'仪器使用时间',
		    '%pi' => '实验室P.I.姓名',
			'%user'=>'实验室成员',
			'%equipment' =>'使用的仪器'
			],
	'send_by'=>[
			'email' => ['通过电子邮件发送', 1],
			'messages' => ['通过消息中心发送', 1],
		],
];
