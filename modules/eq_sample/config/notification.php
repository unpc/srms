<?php

$config['classification']['user']["eq_sample\004仪器送样的相关消息提醒"][] = 'eq_sample.add_sample.sender';
$config['classification']['user']["eq_sample\004仪器送样的相关消息提醒"][] = 'eq_sample.edit_sample.sender';
$config['classification']['user']["eq_sample\004仪器送样的相关消息提醒"][] = 'eq_sample.delete_sample.sender';
$config['classification']['equipment_incharge']["eq_sample\004负责仪器的送样申请的相关消息提醒"][] = 'eq_sample.add_sample.eq_contact';
$config['classification']['equipment_incharge']["eq_sample\004负责仪器的送样申请的相关消息提醒"][] = 'eq_sample.delete_sample.eq_contact';
$config['classification']['lab_pi']["labs\004实验室成员送样相关消息提醒"][] = 'eq_sample.add_sample.pi';
$config['classification']['lab_pi']["labs\004实验室成员送样相关消息提醒"][] = 'eq_sample.edit_sample.pi';
$config['classification']['lab_pi']["labs\004实验室成员送样相关消息提醒"][] = 'eq_sample.delete_sample.pi';


//add sample
$config['eq_sample.add_sample.sender'] = [
	'description'=>'设置用户申请提醒消息',
	'title'=>'送样申请消息提醒',
	'body'=>'您好:\n\n您于%time对仪器%eq_name进行送样申请.',
    'i18n_module'=>'eq_sample',
	'strtr'=>[
        '%time'=> '送样申请添加时间',
        '%eq_name'=> '送样申请仪器',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_sample.add_sample.eq_contact'] = [
	'description'=>'设置负责仪器接受送样申请提醒消息',
	'title'=>'负责仪器接受送样申请消息提醒',
	'body'=>'您好:\n\n您负责的仪器%eq_name于%time收到%sender的送样申请.',
    'i18n_module'=>'eq_sample',
	'strtr'=>[
        '%eq_name'=> '送样申请仪器',
        '%time'=> '送样申请添加时间',
        '%sender'=> '送样申请人员'
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_sample.add_sample.pi'] = [
	'description'=>'设置实验室用户申请提醒消息',
	'title'=>'实验室成员送样申请消息提醒',
	'body'=>'您好:\n\n您实验室内成员%user于%time对仪器%eq_name进行送样申请.',
    'i18n_module'=>'eq_sample',
	'strtr'=>[
        '%time'=> '送样申请添加时间',
        '%eq_name'=> '送样申请仪器',
        '%user'=> '送样申请人员'
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

//edit
$config['eq_sample.edit_sample.sender'] =  [
    'description'=>'设置用户送样记录被修改的消息提醒',
    'title'=> '仪器送样记录修改提醒',
    'body'=> '您好:\n\n您对仪器%eq_name的送样记录[%id]于%time被%user修改, 修改信息变化如下:\n修改前:\n申请人: %old_sender\n送样数: %old_count\n送样时间: %old_dtsubmit\n取样时间: %old_dtpickup\n送样状态: %old_status\n机主备注信息: %old_note\n修改后:\n申请人: %new_sender\n送样数: %new_count\n送样时间: %new_dtsubmit\n取样时间: %new_dtpickup\n送样状态: %new_status\n机主备注信息: %new_note',
    'i18n_module'=> 'eq_sample',
    'strtr'=>[
        '%eq_name' => '送样申请仪器',
        '%id'=> '送样记录编号',
        '%time'=> '修改送样时间',
        '%user'=> '送样修改人员',
        '%old_sender'=> '修改前的送样人员',
        '%old_count'=> '修改前送样数',
        '%old_dtsubmit'=> '修改前送样时间',
        '%old_dtpickup'=> '修改前取样时间',
        '%old_status'=> '修改前送样状态',
        '%old_note'=> '修改前机主备注信息',
        '%new_sender'=> '修改后的送样人员',
        '%new_count'=> '修改后送样数',
        '%new_dtsubmit'=> '修改后送样时间',
        '%new_dtpickup'=> '修改后取样时间',
        '%new_status'=> '修改后送样状态',
        '%new_note'=> '修改后机主备注信息'
    ],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_sample.edit_sample.pi'] =  [
    'description'=>'设置实验室成员送样记录被修改的消息提醒',
    'title'=> '实验室成员仪器送样记录修改提醒',
    'body'=> '您好:\n\n您实验室成员%old_sender对仪器%eq_name的送样记录(#%id)于%time被%user修改, 修改信息变化如下:\n修改前:\n申请人: %old_sender\n送样数: %old_count\n送样时间: %old_dtsubmit\n取样时间: %old_dtpickup\n送样状态: %old_status\n机主备注信息: %old_note\n修改后:\n申请人: %new_sender\n送样数: %new_count\n送样时间: %new_dtsubmit\n取样时间: %new_dtpickup\n送样状态: %new_status\n机主备注信息: %new_note',
    'i18n_module'=> 'eq_sample',
    'strtr'=>[
        '%eq_name' => '送样申请仪器',
        '%id'=> '送样记录编号',
        '%time'=> '修改送样时间',
        '%user'=> '送样修改人员',
        '%old_sender'=> '修改前的送样人员',
        '%old_count'=> '修改前送样数',
        '%old_dtsubmit'=> '修改前送样时间',
        '%old_dtpickup'=> '修改前取样时间',
        '%old_status'=> '修改前送样状态',
        '%old_note'=> '修改前机主备注信息',
        '%new_sender'=> '修改后的送样人员',
        '%new_count'=> '修改后送样数',
        '%new_dtsubmit'=> '修改后送样时间',
        '%new_dtpickup'=> '修改后取样时间',
        '%new_status'=> '修改后送样状态',
        '%new_note'=> '修改后机主备注信息'
    ],
];

//delete
$config['eq_sample.delete_sample.sender'] = [
    'description'=>'设置用户送样记录删除提醒',
    'title'=> '送样预约记录删除提醒',
    'body'=> '您好:\n\n您对仪器%eq_name的送样预约记录(#%id)于%time被%user删除.',
    'i18n_module'=> 'eq_sample',
	'strtr'=>[
        '%eq_name'=> '送样申请仪器',
        '%id'=>'送样申请编号',
        '%time'=> '送样申请删除时间',
        '%user'=> '送样申请删除人员',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_sample.delete_sample.eq_contact'] = [
    'description'=>'设置负责仪器送样记录删除提醒',
    'title'=> '负责仪器送样记录删除提醒',
    'body'=> '您好:\n\n%sender对您负责的仪器%eq_name的的送样预约记录(#%id)于%time被%user删除.',
    'i18n_module'=> 'eq_sample',
    'strtr'=>[
        '%sender'=> '送样申请人员',
        '%eq_name'=> '负责仪器',
        '%id'=>'送样申请编号',
        '%time'=> '送样申请删除时间',
        '%user'=> '送样申请删除人员',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_sample.delete_sample.pi'] = [
    'description'=>'设置实验室成员送样记录删除提醒',
    'title'=> '实验室成员送样记录删除提醒',
    'body'=> '您好:\n\n您实验室成员%sender对仪器%eq_name的送样预约记录(#%id)于%time被%user删除.',
    'i18n_module'=> 'eq_sample',
	'strtr'=>[
        '%sender'=> '实验室成员',
        '%eq_name'=> '送样申请仪器',
        '%id'=>'送样申请编号',
        '%time'=> '送样申请删除时间',
        '%user'=> '送样申请删除人员',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
    ]
];

$config['eq_sample.templates'][] = 'notification.eq_sample.add_sample.sender';
$config['eq_sample.templates'][] = 'notification.eq_sample.add_sample.eq_contact';
$config['eq_sample.templates'][] = 'notification.eq_sample.add_sample.pi';
$config['eq_sample.templates'][] = 'notification.eq_sample.edit_sample.sender';
$config['eq_sample.templates'][] = 'notification.eq_sample.edit_sample.pi';
$config['eq_sample.templates'][] = 'notification.eq_sample.delete_sample.sender';
$config['eq_sample.templates'][] = 'notification.eq_sample.delete_sample.eq_contact';
$config['eq_sample.templates'][] = 'notification.eq_sample.delete_sample.pi';
