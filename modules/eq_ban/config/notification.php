<?php
$config['classification']['user']["equipments\004仪器使用的相关消息提醒"][] = 'eq_ban.eq_banned.eq';
$config['classification']['user']["equipments\004仪器使用的相关消息提醒"][] = 'eq_ban.eq_banned.tag';
$config['classification']['user']["equipments\004仪器使用的相关消息提醒"][] = 'eq_ban.eq_banned';

$config['eq_ban.eq_banned.eq'] = [
	'description'=>'设置用户被加入仪器黑名单的提醒消息',
	'title'=>'警告: 您已经被加入%equipment 使用黑名单! ',
	'body'=>'%user, 您好: \n\n您因为以下原因被 %incharge 加入了 %equipment 的使用黑名单!\n%reason. \n如有任何疑问, 请联系: %contact_info',
	'i18n_module'=>'eq_ban',
	'strtr'=>[
        '%user'=> '用户姓名',
        '%equipment'=> '仪器名称',
        '%reason'=> '用户被加入黑名单的原因',
        '%incharge' => '操作人',
        '%contact_info'=>'联系信息',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ],
];

$config['eq_ban.eq_banned.tag'] = [
	'description'=>'设置用户被加入平台黑名单的提醒消息',
	'title'=>'警告: 您已经被加入%tag平台仪器 使用黑名单! ',
	'body'=>'%user, 您好: \n\n您因为以下原因被 %incharge 加入了 %tag 平台仪器的使用黑名单!\n%reason. \n如有任何疑问, 请联系: %contact_info',
	'i18n_module'=>'eq_ban',
	'strtr'=>[
        '%user'=> '用户姓名',
        '%tag'=> '平台名称',
        '%reason'=> '用户被加入黑名单的原因',
        '%incharge' => '操作人',
        '%contact_info'=>'联系信息',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ],
];

$config['eq_ban.eq_banned'] = [
	'description'=>'设置用户被加入黑名单的提醒消息',
	'title'=>'警告: 您已经被加入仪器使用黑名单! ',
	'body'=>'%user, 您好: \n\n您因为以下原因被管理员加入了仪器使用黑名单! \n%reason. ',
	'i18n_module'=>'eq_ban',
	'strtr'=>[
        '%user'=> '用户姓名',
        '%reason'=> '用户被加入黑名单的原因',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];
