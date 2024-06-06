<?php
$config['classification']['user']['#name'] = '普通用户';
$config['classification']['user']['#module'] = 'people';
$config['classification']['user']['#enable_callback'] = 'People::notif_classification_enable_callback';

$config['handlers']['email'] = [
	'class'=>'Notification_Email',
    'text'=>'通过电子邮件发送',
    'module_name'=>'people',
    'name'=>'电子邮件',
    'default_send'=> TRUE,      //默认发送
    'default_receive'=> FALSE,   //默认不接收
]; 
if (!$GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['classification']['user']["people\004用户账号的相关消息提醒"][] = 'people.activate';
	$config['classification']['user']["people\004用户账号的相关消息提醒"][] = 'people.signup';
	$config['classification']['user']["people\004用户账号的相关消息提醒"][] = 'people.add';
}
$config['people.add'] = [
	'description'=>'管理员成功添加新用户的消息提醒',
	'title'=>'%user, 您的帐号开通了',
	'body'=>'%user, 您好:\n\n帐号已经添加, 请您登录使用. \n帐号: %login\t密码: %password',
	'i18n_module'=>'people',
	'strtr'=>[
		    '%login'=> '登录帐号',
			'%user'=> '用户姓名',
			'%password'=>'登录密码',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
        'messages'=> ['通过消息中心发送', 1]
	],
    'receive_by'=> [
        'email'=> TRUE,
    ],
];

$config['people.signup'] = [
	'description'=>'用户注册时的消息提醒',
	'title'=>'%user, 您的帐号注册成功',
	'body'=>'%user, 您好:\n\n帐号注册成功, 请您联系: \n%lab:  %lab_contact\n或PI:  %pi\n电话:  %pi_phone\n电子邮箱:  %pi_email\n进行激活. ',
	'i18n_module'=>'people',
	'strtr'=>[
		'%user'=> '用户姓名',
		'%lab' => '实验室名称',
		'%lab_contact' => '实验室联系方式',
		'%pi' => 'PI姓名',
		'%pi_phone' => 'PI联系电话',
		'%pi_email' => 'PI电子邮箱'
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
    'receive_by'=> [
        'email'=> TRUE,
    ],
];

$config['people.signup.auto.active'] = [
	'description'=>'用户注册时的消息提醒',
	'title'=>'%user, 您的帐号注册成功',
	'body'=>'%user, 您好:\n\n帐号注册成功, 请采用此账号直接登录平台. ',
	'i18n_module'=>'people',
	'strtr'=>[
		'%user'=> '用户姓名',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
    'receive_by'=> [
        'email'=> TRUE,
    ],
];

$config['people.activate'] = [
	'description'=>'管理员激活用户的消息提醒',
	'title'=>'%user, 您的帐号激活了',
	'body'=>'%user, 您好: \n帐号已经激活, 请您登录使用. ',
	'i18n_module'=>'people',
	'strtr'=>[
		    '%login'=> '登录帐号',
			'%user'=> '用户姓名',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
    'receive_by'=> [
        'email'=> TRUE,
    ],
];

$config['classification']['account_admin']['#name'] = '账号管理员';
$config['classification']['account_admin']['#module'] = 'people';
$config['classification']['account_admin']['#enable_callback'] = 'People::admin_notif_classification_enable_callback';

$config['classification']['account_admin']["people\004用户注册相关消息提醒"][] = 'people.signup.admin';
$config['people.signup.admin'] = [
    'description' => '新用户注册的消息提醒',
    'title' => '新用户已经申请注册！',
    'body' => '%admin, 您好:\n\n新用户%user已经申请注册！',
    'i18n_module' => 'people',
    'strtr' => [
        '%admin' => '管理员',
        '%user' => '注册用户',
    ],
    'send_by' => [
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1]
    ],
    'receive_by'=> [
        'email' => FALSE,
		'messages' => FALSE
    ],
];