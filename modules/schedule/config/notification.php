<?php

$config['classification']['user']["schedule\004日程安排消息提醒"][] = 'schedule.user.add_event.to_organizer';
$config['classification']['user']["schedule\004日程安排消息提醒"][] = 'schedule.lab.delete_event.to_people';
$config['classification']['user']["schedule\004日程安排消息提醒"][] = 'schedule.lab.add_event.to_people';

/* 增加 */
$config['schedule.lab.add_event.to_people'] = [
	'description' => '用户收到新的日程消息提醒',
	'title' => 'LabScout LIMS日程提醒: %name',
	'body' => '%user, 您好:\n\n您的实验室有新的日程通知, 具体内容如下:\n\n日程主题: %name\n发起人: %organizer\n主讲人: %speaker\n\n日程时间: %dtstart - %dtend\n会议室: %meeting\n备注: %description\n\n如需查看, 详细地址链接如下:\n%link\n\nLabScout LIMS Team',
	'strtr' => [
		'%name' => '日程主题',
		'%user' => '用户姓名',
		'%organizer' => '发起人',
		'%speaker' => '主讲人',
		'%dtstart' => '开始时间',
		'%dtend' => '结束时间',
		'%meeting' => '会议室',	
		'%description' => '备注信息',
		'%link' => '链接地址',
	],
	'i18n_module' => 'schedule',
	'send_by' => [
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
    'receive_by'=> [
        'email'=> TRUE,
    ],
];
/* 删除 */
$config['schedule.lab.delete_event.to_people'] = [
	'description' => '用户收到日程被取消的消息提醒',
	'title' => 'LabScout LIMS日程提醒: [%name] 已被取消',
	'body' => '%user, 您好:\n\n您实验室的以下日程已被取消:\n\n日程主题: %name\n发起人: %organizer\n主讲人: %speaker\n\n日程时间: %dtstart - %dtend\n会议室: %meeting\n备注: %description\n取消原因: %cancel_reason\n\n如需查看, 详细地址链接如下:\n%link\n\nLabScout LIMS Team',
	'strtr' => [
		'%name' => '日程主题',
		'%user' => '用户姓名',
		'%organizer' => '发起人',
		'%speaker' => '主讲人',
		'%dtstart' => '开始时间',
		'%dtend' => '结束时间',
		'%meeting' => '会议室',	
		'%description' => '备注信息',
		'%link' => '链接地址',
        '%cancel_reason'=> '取消原因',
	],
	'i18n_module' => 'schedule',
	'send_by' => [
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
    'receive_by'=> [
        'email'=> TRUE,
    ],
];

$config['schedule.user.add_event.to_organizer'] = [
	'description'=>'用户安排新的个人日程消息提醒',
	'title'=>'提醒: 您安排了新的个人日程',
	'body'=>'%user, 您好: \n\n您制定了新的日程安排: %name.\n\n日程时间为 %dtstart - %dtend.\n\n备注信息: \n%description\n\n如需查看, 详细地址链接如下: \n%link.',
	'i18n_module'=>'schedule',
	'strtr'=>[
		'%name'=> '日程主题',
        '%user' => '用户姓名',
        '%dtstart' => '开始时间',
        '%dtend' => '结束时间',
		'%description' => '备注信息',
		'%link' => '链接地址',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
    'receive_by'=> [
        'email'=> TRUE,
    ],
];
