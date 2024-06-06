<?php
$config['server'] = [
    'addr' => 'http://localhost:8041',
    'salt' => '$1$6YNrWK2z$',
];

$config['rpc_token'] = 'c2hvY2t0cm9vcGVyaW5hc3R1cG9y';

if (!$GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['classification']['user']['用户@相关的消息提醒'][] = 'at_user';
}
$config['at_user'] = [
	'description'=>'设置用户被@时的消息提醒',
	'title'=>'提醒: %user 在 %object 的评论提到了您',
	'body'=>'%at_user, 您好: \n\n%user 在 %link 的评论中提到了您, 具体内容如下: \n\n %content',
	'strtr'=>[
		'%object' => '评论对象',
        '%user'=>'用户姓名',
        '%at_user'=> '被提到的用户姓名',
        '%link'=> '评论链接',
        '%content'=>'评论内容'
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1]
    ],
];

$config['send_to_uno'] = false; // 是否采用uno的消息中心
