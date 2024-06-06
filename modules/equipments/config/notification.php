<?php

$config['classification']['equipment_incharge']['#name'] = '仪器负责人';
$config['classification']['equipment_incharge']['#module'] = 'equipments';
$config['classification']['equipment_incharge']['#enable_callback'] = 'Equipments::notif_classification_enable_callback';

$config['classification']['user']["equipments\004仪器使用的相关消息提醒"][] = 'equipments.nofeedback';

$config['classification']['user']["equipments\004仪器使用记录的相关消息提醒"][] = 'equipments.add_record_to_user';
$config['classification']['equipment_incharge']["equipments\004仪器使用记录的相关消息提醒"][] = 'equipments.add_record_to_contact';
$config['classification']['lab_pi']["equipments\004仪器使用记录的相关消息提醒"][] = 'equipments.add_record_to_pi';

$config['classification']['user']["equipments\004仪器使用记录的相关消息提醒"][] = 'equipments.edit_record_to_user';
$config['classification']['equipment_incharge']["equipments\004仪器使用记录的相关消息提醒"][] = 'equipments.edit_record_to_contact';
$config['classification']['lab_pi']["equipments\004仪器使用记录的相关消息提醒"][] = 'equipments.edit_record_to_pi';

$config['classification']['user']["equipments\004仪器使用记录的相关消息提醒"][] = 'equipments.delete_record_to_user';
$config['classification']['equipment_incharge']["equipments\004仪器使用记录的相关消息提醒"][] = 'equipments.delete_record_to_contact';
$config['classification']['lab_pi']["equipments\004仪器使用记录的相关消息提醒"][] = 'equipments.delete_record_to_pi';

$config['classification']['user']["equipments\004仪器培训的相关消息提醒"][] = 'equipments.training_approved';
$config['classification']['user']["equipments\004仪器培训的相关消息提醒"][] = 'equipments.training_deleted';
$config['classification']['user']["equipments\004仪器培训的相关消息提醒"][] = 'equipments.training_rejected';
$config['classification']['user']["equipments\004仪器培训的相关消息提醒"][] = 'equipments.training_removed';
$config['classification']['user']["equipments\004仪器培训的相关消息提醒"][] = 'equipments.training_before_delete';
$config['classification']['user']["equipments\004仪器培训的相关消息提醒"][] = 'equipments.training.deleted.period';

$config['classification']['equipment_incharge']["equipments\004负责仪器的故障报告的相关消息提醒"][] = 'equipments.report_problem';
$config['classification']['equipment_incharge']["equipments\004负责仪器的培训的相关消息提醒"][] = 'equipments.incharge_training_apply';

$config['equipments.training_apply'] = [
	'description'=>'设置用户申请培训提醒消息',
	'title'=>'提醒: 有人申请仪器%equipment使用培训!',
	'body'=>'%incharge, 您好:\n\n用户 %user 向您负责的仪器 %equipment 申请培训.',
	'i18n_module' => 'equipments',
	'strtr'=>[
		'%incharge' => '管理员姓名',
        '%user' => '用户姓名',
        '%equipment' => '设备名称',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['equipments.incharge_training_apply'] = [
	'description'=>'设置用户申请培训提醒消息',
	'title'=>'提醒: 有人申请仪器%equipment使用培训!',
	'body'=>'%incharge, 您好:\n\n用户 %user 向您负责的仪器 %equipment 申请培训. %desc',
	'i18n_module' => 'equipments',
	'strtr'=>[
		'%incharge' => '管理员姓名',
        '%user' => '用户姓名',
        '%equipment' => '设备名称',
        '%desc' => '授权备注',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['equipments.training_rejected'] = [
	'description'=>'设置用户未通过培训提醒消息',
	'title'=>'提醒: 您的仪器培训申请被拒!',
	'body'=>'%user, 您好:\n\n您向仪器 %equipment 提交的培训申请被仪器管理员 %incharge 拒绝.',
	'i18n_module' => 'equipments',
	'strtr'=>[
		'%incharge' => '管理员姓名',
        '%user'=> '用户姓名',
        '%equipment'=> '设备名称',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['equipments.incharge_training_rejected'] = [
	'description'=>'设置用户未通过培训提醒消息',
	'title'=>'提醒: 您的仪器培训申请被拒!',
	'body'=>'%user, 您好:\n\n您向仪器 %equipment 提交的培训申请被仪器管理员 %incharge 拒绝. %desc',
	'i18n_module' => 'equipments',
	'strtr'=>[
		'%incharge' => '管理员姓名',
        '%user'=> '用户姓名',
        '%equipment'=> '设备名称',
        '%desc' => '培训备注'
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['equipments.training_deleted'] = [
	'description'=>'设置用户培训失效的提醒消息',
	'title'=>'提醒: 您的仪器培训证书已失效!',
	'body'=>'%user, 您好:\n\n您需要重新参加 %equipment 的培训后, 才能使用该仪器. 如有疑问, 请您联系管理员 %incharge.',
	'i18n_module' => 'equipments',
	'strtr'=>[
		'%incharge' => '管理员姓名',
        '%user'=> '用户姓名',
        '%equipment'=> '设备名称',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['equipments.training_approved'] = [
	'description'=>'设置用户通过培训提醒消息',
	'title'=>'提醒: 您已经通过仪器培训!',
	/* NO.STORY#38(xiaopei.li@2011.03.07) 由于添加了新的通过培训的机制, 所以以前的提醒消息需要更新 */
	// 'body'=>'%user, 您好: \n\n您向仪器 %equipment 的培训申请 已被仪器管理员 %incharge 同意.',
	'body'=>'%user, 您好: \n\n您已通过仪器 %equipment 的培训, 可以使用了.',
	'i18n_module' => 'equipments',
	'strtr'=>[
		'%incharge' => '管理员姓名',
        '%user'=> '用户姓名',
        '%equipment'=> '设备名称',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['equipments.incharge_training_approved'] = [
	'description'=>'设置用户通过培训提醒消息',
	'title'=>'提醒: 您已经通过仪器培训!',
	/* NO.STORY#38(xiaopei.li@2011.03.07) 由于添加了新的通过培训的机制, 所以以前的提醒消息需要更新 */
	// 'body'=>'%user, 您好: \n\n您向仪器 %equipment 的培训申请 已被仪器管理员 %incharge 同意.',
	'body'=>'%user, 您好: \n\n您已通过仪器 %equipment 的培训, 可以使用了. %desc',
	'i18n_module' => 'equipments',
	'strtr'=>[
		'%incharge' => '管理员姓名',
        '%user'=> '用户姓名',
        '%equipment'=> '设备名称',
        '%desc' => '授权备注',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['equipments.training_removed'] = [
	'description'=>'设置删除用户培训/授权资质的提醒消息',
	'title'=>'提醒: 您的仪器通过授权已被移除!',
	'body'=>'%user, 您好: \n\n您在仪器 %equipment 下的培训授权已被移除.',
	'i18n_module' => 'equipments',
	'strtr'=>[
        '%user'=> '用户姓名',
        '%equipment'=> '设备名称',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['equipments.nofeedback'] = [
	'description'=>'设置用户使用仪器但没有填写反馈信息的提醒消息',
	'title'=>'提醒: 请您填写仪器使用反馈信息',
	'body'=>'%user, 您好:\n\n请您填写仪器 %equipment 的使用反馈信息!',
	'i18n_module' => 'equipments',
	'strtr'=>[
        '%user'=> '用户姓名',
	    '%equipment'=> '设备名称',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['equipments.report_problem'] = [
	'description'=>'设置用户对仪器提交故障报告的消息提醒',
	'title'=>'消息: 用户 %user 对 %equipment 提交了一份故障报告!',
	'body'=>'%incharge, 您好: \n\n%user 提交了一份关于 %equipment 的故障报告:\n%report',
	'i18n_module' => 'equipments',
	'strtr'=>[
		'%incharge' => '管理员',
        '%user' => '用户姓名',
	    '%equipment' => '设备名称',
	    '%report' =>'故障报告',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];


$config['equipments.add_record_to_user'] = [
    'description'=>'设置添加仪器使用记录后给使用者发送的消息提醒',
    'title'=>'提醒: %edit_user为您添加了使用记录',
    'body'=>'%user, 您好:\n\n%edit_user 于%time 为您添加了 %equipment的使用记录 (编号%record_id):\n使用时间: %dtstart - %dtend \n样品数: %sample_num\n\n如需查看, 详细地址链接如下:\n%link\n\n用户 %edit_user 的联系方式:\n电话: %contact_phone\nEmail: %contact_email\n',
    'i18n_module' => 'equipments',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%edit_user'=>'修改人员名称',
        '%time'=>'添加时间',
        '%record_id'=>'使用记录编号',
        '%dtstart'=>'开始时间',
        '%dtend'=>'结束时间',
        '%sample_num'=>'样品数',
        '%link' => '链接地址',
		'%contact_phone'=>'修改者电话',
		'$contact_email'=>'修改者电子信箱'
    ],
    'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['equipments.add_record_to_contact'] = [
    'description'=>'设置添加仪器使用记录后给仪器联系人发送的消息提醒',
    'title'=>'提醒: %edit_user为%user添加了使用记录',
    'body'=>'%contact, 您好:\n\n%edit_user 于%time 为 %user 添加了您负责的仪器 %equipment的使用记录 (编号%record_id):\n使用时间: %dtstart - %dtend \n样品数: %sample_num\n\n如需查看, 详细地址链接如下:\n%link',
    'i18n_module' => 'equipments',
    'strtr'=>[
    	'%contact'=>'仪器联系人姓名',
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%time'=>'添加时间',
        '%edit_user'=>'修改人员名称',
        '%record_id'=>'使用记录编号',
        '%dtstart'=>'开始时间',
        '%dtend'=>'结束时间',
        '%sample_num'=>'样品数',
        '%link' => '链接地址',
    ],
    'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['equipments.add_record_to_pi'] = [
    'description'=>'设置添加仪器使用记录后给实验室PI发送的消息提醒',
    'title'=>'提醒: %edit_user为您实验室中的成员添加了使用记录',
    'body'=>'%pi, 您好:\n\n%edit_user 于 %time 为您实验室的用户 %user 添加了 %equipment的使用记录 (编号%record_id):\n使用时间: %dtstart - %dtend\n样品数: %sample_num\n\n如需查看, 详细地址链接如下:\n%link',
    'i18n_module' => 'equipments',
    'strtr'=>[
        '%pi'=>'实验室P.I.姓名',
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%time'=>'添加时间',
        '%edit_user'=>'修改人员名称',
        '%record_id'=>'使用记录编号',
        '%dtstart'=>'开始时间',
        '%dtend'=>'结束时间',
        '%sample_num'=>'样品数',
        '%link' => '链接地址',
    ],
    'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['equipments.edit_record_to_user'] = [
    'description'=>'设置修改了仪器使用记录后给使用者发送的消息提醒',
    'title'=>'提醒: 您的使用记录被修改',
    'body'=>'%user, 您好:\n\n%edit_user 于 %time 修改了您在 %equipment的使用记录(编号%record_id). 修改信息如下:\n%edit_content\n\n如需查看, 详细地址链接如下:\n%link\n\n用户 %edit_user 的联系方式:\n电话: %contact_phone\nEmail: %contact_email\n',
    'i18n_module' => 'equipments',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%time'=>'修改时间',
        '%edit_user'=>'修改人员名称',
        '%record_id'=>'使用记录编号',
        '%edit_content'=>'修改内容',
        '%link' => '链接地址',
        '%contact_phone'=>'修改者电话',
		'$contact_email'=>'修改者电子信箱'
    ],
    'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['equipments.edit_record_to_contact'] = [
    'description'=>'设置修改了仪器使用记录后给仪器联系人发送的消息提醒',
    'title'=>'提醒: %user在%equipment中的使用记录被修改',
    'body'=>'%contact, 您好:\n\n%edit_user 于 %time 修改了 %user 在您负责的仪器 %equipment 中的使用记录（编号%record_id）, 修改信息如下:\n%edit_content\n\n如需查看, 详细地址链接如下:\n%link\n\n用户 %user 的联系方式:\n电话: %user_phone\nEmail: %user_email\n',
    'i18n_module' => 'equipments',
    'strtr'=>[
    	'%contact'=>'仪器联系人',
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%time'=>'修改时间',
        '%edit_user'=>'修改人员名称',
        '%record_id'=>'使用记录编号',
        '%edit_content'=>'修改内容',
        '%link' => '链接地址',
        '%user_phone' => '用户电话',
		'%user_email' => '用户电子信箱',
    ],
    'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['equipments.edit_record_to_pi'] = [
    'description'=>'设置修改了仪器使用记录后给实验室PI发送的消息提醒',
    'title'=>'提醒: 您实验室中的用户%user的使用记录被修改',
    'body'=>'%pi, 您好:\n\n%edit_user 于 %time 修改了您实验室中的成员 %user 在 %equipment 中的使用记录（编号%record_id）, 修改信息如下:\n%edit_content\n\n如需查看, 详细地址链接如下:\n%link',
    'i18n_module' => 'equipments',
    'strtr'=>[
    	'%pi' => '实验室P.I.姓名',
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%time'=>'修改时间',
        '%edit_user'=>'修改人员名称',
        '%record_id'=>'使用记录编号',
        '%edit_content'=>'修改内容',
        '%link' => '链接地址',
    ],
    'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['equipments.delete_record_to_user'] = [
    'description'=>'设置删除了仪器使用记录后给使用者发送的消息提醒',
    'title'=>'提醒: 您在%equipment中的使用记录被删除',
    'body'=>'%user, 您好:\n\n%edit_user 于 %time 删除了您在 %equipment 中的使用记录 (编号%record_id).\n原使用记录时间为: %old_dtstart - %old_dtend\n 原样品数为: %old_sample_num\n\n用户 %edit_user 的联系方式:\n电话: %contact_phone\nEmail: %contact_email\n',
    'i18n_module' => 'equipments',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
		'%time'=>'删除时间',
        '%edit_user'=>'修改人员名称',
        '%record_id'=>'使用记录编号',
		'%old_dtstart'=>'原使用记录开始时间',
		'%old_dtend'=>'原使用记录结束时间',
        '%old_sample_num'=>'原样品数',
		'%contact_phone'=>'修改者电话',
		'$contact_email'=>'修改者电子信箱',
    ],
    'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['equipments.delete_record_to_contact'] = [
    'description'=>'设置删除了仪器使用记录后给仪器联系人发送的消息提醒',
    'title'=>'提醒: %user在%equipment中的使用记录被删除',
    'body'=>'%contact, 您好:\n\n%edit_user 于 %time 删除了 %user 在您负责的仪器 %equipment 中的使用记录 (编号%record_id).\n原使用记录时间为: %old_dtstart - %old_dtend\n原样品数为: %old_sample_num',
    'i18n_module' => 'equipments',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
		'%contact'=>'仪器联系人',
        '%time'=>'删除时间',
        '%edit_user'=>'修改人员名称',
        '%record_id'=>'使用记录编号',
		'%old_dtstart'=>'原使用记录开始时间',
		'%old_dtend'=>'原使用记录结束时间',
        '%old_sample_num'=>'原样品数',
    ],
    'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['equipments.delete_record_to_pi'] = [
    'description'=>'设置删除了仪器使用记录后给实验室PI发送的消息提醒',
    'title'=>'提醒: 您实验室中的成员%user在%equipment中的使用记录被删除',
    'body'=>'%pi, 您好:\n\n%edit_user 于 %time 删除了您实验室中的用户 %user 在 %equipment 中的使用记录 (编号%record_id).\n原使用记录时间为: %old_dtstart - %old_dtend\n原样品数为: %old_sample_num',
    'i18n_module' => 'equipments',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
		'%pi'=>'实验室P.I.姓名',
        '%time'=>'删除时间',
        '%edit_user'=>'修改人员名称',
        '%record_id'=>'使用记录编号',
		'%old_dtstart'=>'原使用记录开始时间',
		'%old_dtend'=>'原使用记录结束时间',
        '%old_sample_num'=>'原样品数',
    ],
    'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['equipments_conf'] = [
    'notification.equipments.nofeedback',
    'notification.equipments.report_problem',
    'notification.equipments.add_record_to_user',
    'notification.equipments.add_record_to_contact',
    'notification.equipments.add_record_to_pi',
    'notification.equipments.edit_record_to_user',
    'notification.equipments.edit_record_to_contact',
    'notification.equipments.edit_record_to_pi',
    'notification.equipments.delete_record_to_user',
    'notification.equipments.delete_record_to_contact',
    'notification.equipments.delete_record_to_pi',
    'notification.equipments.incharge_training_apply',
    'notification.equipments.incharge_training_approved',
    'notification.equipments.incharge_training_rejected',
    'notification.equipments.training_deleted',
    'notification.equipments.training_removed',
    'notification.equipments.training_before_delete',
    'notification.equipments.training.deleted.period',
];

$config['equipments.training_before_delete'] = [
    'description'=> '设置用户培训授权即将在7天后失效的提醒消息',
    'title'=> '提醒: 您的仪器培训证书即将在7天后失效!',
    'body'=> '%user, 您好:\n\n您的 %equipment 仪器的培训授权即将在7天后失效, 届时您需要重新参加仪器培训后, 才能继续使用该仪器. 如有疑问, 请您联系管理员 %incharge.',
    'i18n_module'=> 'equipments',
    'strtr'=> [
        '%user'=> '用户姓名',
        '%equipment'=> '设备名称',
        '%incharge'=> '管理员姓名',
    ],
    'send_by'=> [
        'email'=> ['通过电子邮箱发送', 1],
        'messages'=> ['通过消息中心发送', 1],
    ],
    'receive_by'=>[
        'email' => TRUE,
    ]
];

$config['equipments.training.deleted.period'] = [
	'description'=>'设置用户培训后未使用仪器, 导致证书失效的提醒消息',
	'title'=>'提醒: 您的仪器培训证书已失效!',
	'body'=>'%user, 您好:\n\n培训通过后, 您已 %day 未使用 %equipment 仪器, 您需要重新参加仪器培训后, 才能使用该仪器. 如有疑问, 请您联系管理员 %incharge.',
	'i18n_module' => 'equipments',
	'strtr'=>[
		'%incharge' => '管理员姓名',
        '%user'=> '用户姓名',
        '%equipment'=> '设备名称',
        '%day'=> '设置的培训通过后未使用仪器的天数或⽉数',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['eq_reserv.holiday_setted_deleted'] = [
    'description'=>'设置用户预约由于假期设置被删除的提醒',
    'title'=>'提醒: 您在仪器%equipment中的预约由于假期设置被删除',
    'body'=>'%user, 您好:\n\n仪器 %equipment 设置了假期, 假期时间为 %holiday_dtstart~%holiday_dtend,您当前时段的预约被系统删除.\n\n%other_content',
    'i18n_module' => 'eq_reserv',
    'strtr'=>[
        '%user' => '用户姓名',
        '%equipment' => '设备名称',
        '%holiday_dtstart' => '原开始时间',
        '%holiday_dtend' => '原结束时间',
        '%other_content' => '其他信息',
    ],
    'send_by'=>[
//        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

$config['equipments.not_use_notice'] = [
    'description' => '设置仪器长时间未使用消息预警',
    'title' => '预警: 您负责的%equipment未使用预警',
    'body' => '%incharge, 您好:\n\n仪器 %equipment 已经连续%time未使用，请合理安排使用时间.',
    'i18n_module' => 'equipments',
    '#view' => 'equipments:admin/use_notice/notification',
    'strtr' => [
        '%incharge' => '管理员姓名',
        '%equipment' => '设备名称',
        '%time' => '仪器连续未使用时长',
    ],
    'send_by' => [
        'system' => ['通过系统提示弹框', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

$config['equipments.use_more_notice'] = [
    'description' => '设置仪器长时间使用消息预警',
    'title' => '预警: 您负责的%equipment使用超时预警',
    'body' => '%incharge, 您好:\n\n仪器 %equipment 已经连续使用超过%time，请合理安排使用时间.',
    'i18n_module' => 'equipments',
    '#view' => 'equipments:admin/use_notice/notification',
    'strtr' => [
        '%incharge' => '管理员姓名',
        '%equipment' => '设备名称',
        '%time' => '仪器连续使用时长',
    ],
    'send_by' => [
        'system' => ['通过系统提示弹框', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

$config['equipments.use_more_notice_to_user'] = [
    'description' => '设置仪器长时间使用消息预警',
    'title' => '预警: 您使用的%equipment使用时间过长预警',
    'body' => '%user, 您好:\n\n仪器 %equipment 已经连续使用超过%time，请合理安排使用时间.',
    'i18n_module' => 'equipments',
    '#view' => 'equipments:admin/use_notice/notification',
    'strtr' => [
        '%user' => '使用者姓名',
        '%equipment' => '设备名称',
        '%time' => '仪器连续使用时长',
    ],
    'send_by' => [
        'messages' => ['通过消息中心发送', 1],
    ],
    'receive_by' => [
        'messages' => true,
    ],
];

$config['equipments.use_less_notice'] = [
    'description' => '设置仪器使用时间过短消息预警',
    'title' => '预警: 您负责的%equipment使用过短预警',
    'body' => '%incharge, 您好:\n\n仪器 %equipment 已经连续使用少于%time，请合理安排使用时间.',
    'i18n_module' => 'equipments',
    '#view' => 'equipments:admin/use_notice/notification',
    'strtr' => [
        '%incharge' => '管理员姓名',
        '%equipment' => '设备名称',
        '%time' => '仪器连续使用时长',
    ],
    'send_by' => [
        'system' => ['通过系统提示弹框', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

$config['equipments.use_less_notice_to_user'] = [
    'description' => '设置仪器使用时间过短消息预警',
    'title' => '预警: 您使用的%equipment使用时间过短预警',
    'body' => '%user, 您好:\n\n仪器 %equipment 已经连续使用少于%time，请合理安排使用时间.',
    'i18n_module' => 'equipments',
    '#view' => 'equipments:admin/use_notice/notification',
    'strtr' => [
        '%user' => '使用者姓名',
        '%equipment' => '设备名称',
        '%time' => '仪器连续使用时长',
    ],
    'send_by' => [
        'messages' => ['通过消息中心发送', 1],
    ],
    'receive_by' => [
        'messages' => true,
    ],
];
