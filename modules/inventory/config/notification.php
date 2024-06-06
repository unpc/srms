<?php

$config['stock_admin.notification'][] = 'notification.stock.expiration';

$config['stock.expiration'] = [
	'description' => '设置用户收到存货即将过期的提醒消息',
	'title' => '警告: 实验室存货 [%product_name] 将在%expires_on过期!',
	'body' => '%user, 您好: \n\n您实验室的存货 [%product_name] 将在%date过期, 请及时处理!\n\n\n\nLabScout LIMS Team',
	'i18n_module' => 'inventory',
	'strtr'=>[
		'%user' => '用户姓名',
        '%product_name' => '存货名称',
        '%expires_on' => '"今天", 或者"X天后"',
        '%date' => '日期',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
    'receive_by'=> [
        'email'=> TRUE,
    ],
];
