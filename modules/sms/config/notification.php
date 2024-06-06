<?php

$config['handlers']['sms'] = [
	'class'=>'Notification_SMS',
    'text'=>'通过短信发送',
    'module_name'=>'sms',
    'name'=>'短信',
    'default_send'=> FALSE,     //默认不发送
    'default_receive'=> FALSE,  //默认不接收
];

/*
$config['test'] = array(
	'description'=>'短信测试',
	'title'=>'this is title 这是标题',
	'body'=>'this is content 这是内容',
	'send_by'=>array(
		'messages' => array('', 0),
		'email' => array('', 0),
		'sms' => array('通过短信发送', 1),
	),
);
*/
