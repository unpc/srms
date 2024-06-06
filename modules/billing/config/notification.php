<?php

if ($GLOBALS['preload']['billing.single_department']) {
    $config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.account_credit.unique';
    $config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.account_deduction.unique';
    $config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.edit_transaction.unique';
    $config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.edit_credit_line.unique';
    $config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.refill.unique';
}
else {
    $config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.account_credit';
    $config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.account_deduction';
    $config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.edit_transaction';
    $config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.edit_credit_line';
    $config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.refill';
}

//财务账号充值(单财务)
$config['billing.account_credit.unique'] = [
	'description' => '财务账号充值提醒',
    'title'=> '财务账号充值提醒',
    'body'=> '您好!\n%user于%time, 对您实验室财务账号进行充值, 充值金额: %amount.\n\n当前账号余额: %balance.',
    'i18n_module'=> 'billing',
    'strtr'=> [
        '%user'=> '充值人员',
        '%time'=> '充值时间',
        '%amount'=> '充值金额',
        '%balance'=> '账号余额'
    ],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	]
];

//财务账号扣费(单财务)
$config['billing.account_deduction.unique'] = [
	'description' => '财务账号扣费提醒',
    'title'=> '财务账号扣费提醒',
    'body'=> '您好!\n%user于%time, 对您实验室财务账号进行扣费, 扣费金额: %amount.\n\n当前账号余额: %balance.',
    'i18n_module'=> 'billing',
    'strtr'=> [
        '%user'=> '扣费人员',
        '%time'=> '扣费时间',
        '%amount'=> '扣费金额',
        '%balance'=> '账号余额'
    ],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	]
];


//财务明细修改提醒(单财务)
$config['billing.edit_transaction.unique'] = [
	'description' => '财务明细修改提醒',
    'title'=> '财务明细修改提醒',
    'body'=> '您好!\n%user于%time, 对您实验室财务明细[%id]进行修改操作.\n修改前:\n收入: %old_income 支出: %old_outcome\n修改后:\n收入: %new_income 支出: %new_outcome\n\n当前财务账号余额为%balance',
    'i18n_module'=> 'billing',
    'strtr'=> [
        '%user'=> '修改人员',
        '%time'=> '修改时间',
        '%id'=> '财务明细编号',
        '%old_income'=> '修改前收入',
        '%old_outcome'=> '修改前支出',
        '%new_income'=> '修改后收入',
        '%new_outcome'=> '修改后支出',
        '%balance'=> '账号余额'
    ],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	]
];

//修改信用额度(单财务)
$config['billing.edit_credit_line.unique'] = [
	'description' => '财务账号信用额度修改提醒',
    'title'=> '财务账号信用额度修改提醒',
    'body'=> '您好!\n%user于%time, 修改了您实验室财务账号信用额度.\n\n修改前信用额度: %old_credit_line\n修改后信用额度: %new_credit_line',
    'i18n_module'=> 'billing',
    'strtr'=> [
        '%user'=> '修改信用额度操作人员',
        '%time'=> '修改额度时间',
        '%old_credit_line'=> '修改前信用额度',
        '%new_credit_line'=> '修改后信用额度'
    ],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	]
];

//财务账号充值(多财务)
$config['billing.account_credit'] = [
	'description' => '财务账号充值提醒',
    'title'=> '财务账号充值提醒',
    'body'=> '您好!\n%user于%time, 对您实验室在%dept下的财务账号进行充值, 充值金额: %amount.\n\n当前账号余额: %balance.',
    'i18n_module'=> 'billing',
    'strtr'=> [
        '%user'=> '充值人员',
        '%time'=> '充值时间',
        '%amount'=> '充值金额',
        '%balance'=> '账号余额',
        '%dept'=> '财务部门'
    ],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	]
];

//财务账号扣费(多财务)
$config['billing.account_deduction'] = [
	'description' => '财务账号扣费提醒',
    'title'=> '财务账号扣费提醒',
    'body'=> '您好!\n%user于%time, 对您实验室在%dept下的财务账号进行扣费, 扣费金额: %amount.\n\n当前账号余额: %balance.',
    'i18n_module'=> 'billing',
    'strtr'=> [
        '%user'=> '扣费人员',
        '%time'=> '扣费时间',
        '%amount'=> '扣费金额',
        '%balance'=> '账号余额',
        '%dept'=> '财务部门'
    ],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	]
];


//财务明细修改提醒(多财务)
$config['billing.edit_transaction'] = [
	'description' => '财务明细修改提醒',
    'title'=> '财务明细修改提醒',
    'body'=> '您好!\n%user于%time, 对您实验室在%dept下的财务明细[%id]进行修改操作.\n修改前:\n收入: %old_income 支出: %old_outcome\n修改后:\n收入: %new_income 支出: %new_outcome\n\n当前财务账号余额为%balance',
    'i18n_module'=> 'billing',
    'strtr'=> [
        '%user'=> '修改人员',
        '%time'=> '修改时间',
        '%id'=> '财务明细编号',
        '%old_income'=> '修改前收入',
        '%old_outcome'=> '修改前支出',
        '%new_income'=> '修改后收入',
        '%new_outcome'=> '修改后支出',
        '%balance'=> '账号余额',
        '%dept'=> '财务部门'
    ],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	]
];

//修改信用额度(多财务)
$config['billing.edit_credit_line'] = [
	'description' => '财务账号信用额度修改提醒',
    'title'=> '财务账号信用额度修改提醒',
    'body'=> '您好!\n%user于%time, 修改了您实验室在%dept下的财务账号信用额度.\n\n修改前信用额度: %old_credit_line\n修改后信用额度: %new_credit_line',
    'i18n_module'=> 'billing',
    'strtr'=> [
        '%user'=> '修改信用额度操作人员',
        '%time'=> '修改额度时间',
        '%old_credit_line'=> '修改前信用额度',
        '%new_credit_line'=> '修改后信用额度',
        '%dept'=> '财务部门'
    ],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	]
];

//实验室充值提醒(单财务)
$config['billing.refill.unique'] = [
	'#view' => 'billing:admin/refill_notif',
	'description' => '实验室充值提醒',
	'balance' => 0,
	'period' => 7,
	'title' => '实验室 %lab 充值提醒',
	// 'body' => '您好:\n您实验室在财务部门余额低于%alert_line, 请您及时充值, 以免影响您实验室对仪器的正常使用.\n\n当前财务账号余额: %balance',
	'body' => '您好:\n您实验室在财务部门余额的账号余额不足, 请尽快充值, 以免影响您实验室对仪器的正常使用.\n\n当前财务账号余额: %balance',
	'i18n_module'=>'billing',
	'strtr' => [
        '%lab' => '当前实验室',
		'%balance' => '当前财务账号余额',
        // '%alert_line'=> '财务账号最小报警余额'
        // '%min_credit_per'=> '信用额度占比'
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

//实验室充值提醒(多财务)
$config['billing.refill'] = [
	'#view' => 'billing:admin/refill_notif',
	'description' => '实验室充值提醒',
	'balance' => 0,
	'period' => 7,
	'title' => '实验室 %lab 充值提醒',
    // 'body' => '您好:\n您实验室在%department 的余额低于%alert_line, 请您及时充值, 以免影响您实验室对仪器的正常使用.\n\n当前财务账号余额: %balance',
    'body' => '您好:\n您实验室在%department 的余额不足, 请尽快充值, 以免影响您实验室对仪器的正常使用.\n\n当前财务账号余额: %balance',
	'i18n_module'=>'billing',
	'strtr' => [
        '%lab' => '当前实验室',
		'%balance' => '当前财务账号余额',
		'%department' => '财务中心',
        // '%alert_line'=> '财务账号最小报警余额',
		// '%min_credit_per' => '信用额度占比',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];

$config['billing_conf'] = [
    'notification.billing.account_credit',
    'notification.billing.account_deduction',
    'notification.billing.edit_transaction',
    'notification.billing.edit_credit_line'
];

//【通用可配】【上海交通大学医学院免疫所】RQ183304-系统定期给课题组PI发送财务明细 结算通知
$config['classification']['lab_pi']["labs\004实验室经费的相关消息提醒"][] = 'billing.account.detail';
$config['billing.account.detail'] = [
    'description' => '课题组财务结算明细',
    'title'=> '课题组财务结算明细',
    'body'=> 
    '%user老师：\n
    您好!\n
    您负责的课题组在%dept财务部门下仪器使用收费情况如下: \n
    截止%dt_one, 财务余额为: ￥%balance_one
    截止%dt_two, 财务余额为: ￥%balance_two\n
    %dt_one~%dt_two共扣费%outcome元，充值%income元
    本期应交纳：%pay元
    在%dt_one - %dt_two之间, 您负责课题组在%dept财务部门下的收支明细详见附件.\n',
    'i18n_module'=> 'billing',
    'strtr'=> [
        '%user' => '课题组PI',
        '%dept' => '财务部门',
        '%dt_one' => '上次结算时间',
        '%balance_one' => '上次结算余额',
        '%dt_two' => '本次结算时间',
        '%balance_two' => '本次结算余额',
        '%income' => '结算周期内充值金额',
        '%outcome' => '结算周期内扣费金额',
        '%pay' => '结算周期应缴纳费用',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
    'receive_by'=> [
        'email' => TRUE
    ]
];