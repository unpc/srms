<?php
$config['classification']['envmon_admin']['#name'] = '监控对象负责人';
$config['classification']['envmon_admin']['#module'] = 'envmon';
$config['classification']['envmon_admin']['#enable_callback'] = 'Node::notif_classification_enable_callback';

$config['classification']['envmon_admin']["envmon\004传感器数据异常相关消息提醒"][] = 'envmon.sensor.nodata';
$config['classification']['envmon_admin']["envmon\004传感器数据异常相关消息提醒"][] = 'envmon.sensor.warning';

$config['envmon.sensor.warning'] = [
	'description'=>'传感器数据发生异常',
	'title'=>'警告: 您的 %node 下的 %sensor 数据发生异常',
	'body'=>'%user, \n\n您负责的 %node 下的 %sensor 传感器发生异常! \n目前温度为%alert_data, 该传感器温度的正常范围为%standard_start - %standard_end',
	'i18n_module'=>'envmon',
	'strtr'=>[
        '%user'=> '用户姓名',
        '%node'=>'监控对象',
        '%sensor'=> '传感器名称',
        '%alert_data'=> '传感器当前温度',
        '%standard_start'=> '传感器温度正常范围的起始值',
        '%standard_end'=> '传感器温度正常范围的结束值',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
		'sms' => ['通过短信发送', 0]
	],
];

$config['envmon.sensor.nodata'] = [
	'description'=>'传感器接收数据错误',
	'title'=>'警告: 您的 %node 下的 %sensor 接收数据异常',
	'body'=>'%user, \n\n您负责的 %node 下的 %sensor 传感器发生异常! \n %dtstart - %dtend 之间没有接收到数据!',
	'i18n_module'=>'envmon',
	'strtr'=>[
        '%user'=> '用户姓名',
        '%node'=>'监控对象',
        '%sensor'=> '传感器名称',
        '%dtstart' => '监控时间范围起始值',
        '%dtend' => '监控时间范围结束值',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
		'sms' => ['通过短信发送', 0]
	],
];
