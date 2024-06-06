<?php

$config['handlers']['messages'] = [
	'class'=>'Notification_Message',
    'text'=>'通过消息中心发送',
    'module_name'=>'messages',
    'name'=>'消息中心',
    'default_send'=> TRUE,      //默认发送
    'default_receive'=>  TRUE,  //默认接收
];
