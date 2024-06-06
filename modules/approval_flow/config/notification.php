<?php

$config['approval_expired.to_user.sender'] = [
    'description' => '设置审核已逾期消息提醒',
    'title' => '新的%type审核进展',
    'body' => '您好:\n\n您于%time对仪器%eq_name的%type申请.由于逾期未审核被系统自动删除.详情见%approval_url',
    'i18n_module' => 'approval',
    'strtr' => [
        '%type' => '申请类型',
        '%time' => '添加时间',
        '%eq_name' => '申请仪器',
        '%approve_url' => '审批地址'
    ],
    'send_by' => [
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['approval.to_user.sender'] = [
    'description' => '设置审核已处理消息提醒',
    'title' => '新的%type审核进展',
    'body' => '您好:\n\n%user于%time对仪器%eq_name的%type申请.被%auditor审核%state.%extra',
    'i18n_module' => 'approval',
    'strtr' => [
        '%type' => '申请类型',
        '%user' => '申请人',
        '%time' => '添加时间',
        '%eq_name' => '申请仪器',
        '%auditor' => '审核人',
        '%state' => '通过/驳回',
        '%extra' => '补充信息',
    ],
    'send_by'=>[
        'messages' => ['通过消息中心发送', 1],
    ]
];
//流程消息提醒
$config['approval.to_auditor.sender'] = [
    'description' => '设置%type审核已处理消息提醒',
    'title' => '新的%type审核进展',
    'body' => '您好:\n\n用户%user于%time对仪器%eq_name的%type申请,%detail待审核人%auditor审批',
    'i18n_module' => 'approval',
    'strtr' => [
        '%type' => '申请类型',
        '%user' => '申请人',
        '%time' => '添加时间',
        '%eq_name' => '申请仪器',
        '%detail' => '上一步的操作',
        '%auditor' => '待审核人'
    ],
    'send_by'=>[
        'messages' => ['通过消息中心发送', 1],
    ]
];

$config['approval.need_approve_pi_eq_reserv'] = [
    'description' => 'PI收到组内用户预约待审核消息提醒',
    'title' => '预约审核提醒：有一条新的预约申请待您审核！',
    'body' => '%pi, 您好!\n\n您组内成员%user, 于%time提交了一条%equipment的预约申请, 预约时间为：%dtstart-%dtend, 预估金额：¥%money, 请尽快在课题组审核页进行审核.',
    'i18n_module' => 'approval_flow',
    'strtr' => [
        '%pi' => 'PI姓名',
        '%user' => '申请者',
        '%time' => '申请时间',
        '%equipment' => '申请仪器',
        '%dtstart' => '预约起始时间',
        '%dtend' => '预约截止时间',
        '%money' => '预估金额',
    ],
    'send_by' => [
        'messages' => ['通过消息中心发送', 1],
        'email' => ['通过电子邮件发送', 1],
    ]
];

$config['approval.need_approve_pi_eq_sample'] = [
    'description' => 'PI收到组内用户送样待审核消息提醒',
    'title' => '送样审核提醒：有一条新的送样申请待您审核！',
    'body' => '%pi, 您好!\n\n您组内成员%user，于%time提交了一条%equipment的送样申请, 送样时间为：%dtsubmit, 请尽快在课题组审核页进行审核.',
    'i18n_module' => 'approval_flow',
    'strtr' => [
        '%pi' => 'PI姓名',
        '%user' => '申请者',
        '%time' => '申请时间',
        '%equipment' => '申请仪器',
        '%dtsubmit' => '送样时间',
    ],
    'send_by' => [
        'messages' => ['通过消息中心发送', 1],
        'email' => ['通过电子邮件发送', 1],
    ]
];

$config['approval.need_approve_incharge_eq_sample'] = [
    'description' => '机主收到负责仪器的预约待审核消息提醒',
    'title' => '预约审核提醒：有一条新的预约申请待您审核！',
    'body' => '%incharge, 您好!\n\n%user向您负责的%equipment申请预约, 预约时间：%dtstart-%dtend, 预估金额：%money, 请您尽快在负责仪器预约审核页进行审核.',
    'i18n_module' => 'approval_flow',
    'strtr' => [
        '%incharge' => '机主姓名',
        '%user' => '申请者',
        '%equipment' => '申请仪器',
        '%dtstart' => '预约起始时间',
        '%dtend' => '预约截止时间',
        '%money' => '预估金额',
    ],
    'send_by' => [
        'messages' => ['通过消息中心发送', 1],
        'email' => ['通过电子邮件发送', 1],
    ]
];