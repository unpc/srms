<?php
$config['classification']['lab_pi']['#name'] = '实验室PI';
$config['classification']['lab_pi']['#module'] = 'labs';
$config['classification']['lab_pi']['#enable_callback'] = 'Labs::notifi_classification_enable_callback';

if(!$GLOBALS['preload']['gateway.perm_in_uno']){
	$config['classification']['lab_pi']["labs\004实验室成员账号的相关消息提醒"][] = 'labs.register';
}
$config['labs.register'] = [
	'description' => '用户激活时实验室PI的消息提醒',
	'title' => '您实验室中%user的账号已被激活了',
	'body' => '您好%PI：\n您的实验室%lab中,%user的账号已经被激活了。',
	'i18n_module' => 'labs',
	'strtr' => [
		'%user' => '被激活账号',
		'%PI'   => '实验室PI',
		'%lab'  => '实验室',
	],
	'send_by'=>[
		'email' => ['通过电子邮件发送', 1],
		'messages' => ['通过消息中心发送', 1],
	],
];
