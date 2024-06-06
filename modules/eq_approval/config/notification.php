<?php

//PI拒绝预约劵申请通知给学生的信息
$config['approval.reject_reserv_voucher'] = [
	'description' => 'PI拒绝预约劵申请通知给学生的信息',
    'title'=> '提醒: PI拒绝了您申请的预约凭证',
    'body'=> '%user, 您好!\nPI拒绝了您申请的预约凭证.\n凭证信息如下:\n预约仪器:\t%equipment\n仪器负责人:\t%contacts\n关联项目:\t%project\n%type_content实验内容:\t%description\n申请时间:\t%ctime\n\n如需查看, 详细地址链接如下:\n%link\n',
    'i18n_module'=> 'eq_approval',
    'strtr'=> [
        '%user'=> '预约卷申请人',
        '%equipment'=> '预约仪器名称',
        '%contacts'=> '预约仪器负责人',
        '%project'=> '关联项目',
        '%type_content' => '类型数值',
        '%description' => '实验内容描述',
        '%ctime' => '申请时间',
        '%link' => '个人预约凭证链接'
    ],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 0],
		'messages' => ['通过消息中心发送', 1],
	]
];