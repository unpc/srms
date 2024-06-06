<?php

$config['classification']['user']["nfs\004病毒检测结果通知"][] = 'nfs.delete_files.to_people';
$config['classification']['user']["nfs\004病毒检测结果通知"][] = 'nfs.save_files.to_people';

/* 删除 */
$config['nfs.delete_files.to_people'] = [
	'description' => '用户上传文件存在病毒，文件被删除的消息提醒',
	'title' => 'LabScout LIMS文件系统: [%name] 检测不通过',
	'body' => '%user, 您好:\n\n您之前上传的 [%name] 病毒检测未通过，已被删除，如有疑问，请联系管理员!\n\n检测时间: %time',
	'strtr' => [
		'%name' => '文件名称',
		'%user' => '用户姓名',
		'%time' => '开始时间',
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

/* 保存 */
$config['nfs.save_files.to_people'] = [
	'description' => '用户上传文件成功的消息提醒',
	'title' => 'LabScout LIMS文件系统: [%name] 检测已通过',
	'body' => '%user, 您好:\n\n您之前上传的 [%name] 病毒检测已通过，请到相应分区查看文件，如有疑问，请联系管理员!\n\n检测时间: %time',
	'strtr' => [
		'%name' => '文件名称',
		'%user' => '用户姓名',
		'%time' => '开始时间',
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
