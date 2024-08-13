<?php
//独立服务器, 特殊notification配置
$config['server'] = [
   'addr' => 'http://172.17.42.1:8041',
   'salt' => '$1$3GJIOLTW$',
];

$config['rpc_token'] = 'A4QDw5ZYpJ9cagfh';


$config['handlers']['email'] = [
	 'class'=>'Notification_Email',
    'text'=>'通过电子邮件发送',
    'name'=>'电子邮件',
    'default_send'=> TRUE,      //默认发送
    'default_receive'=> FALSE,   //默认不接收
];

$config['handlers']['messages'] = [
    'class'=>'Notification_Message',
    'text'=>'通过消息中心发送',
    'module_name'=>'messages',
    'name'=>'消息中心',
    'default_send'=> TRUE,      //默认发送
    'default_receive'=>  true,  //默认不接收
];