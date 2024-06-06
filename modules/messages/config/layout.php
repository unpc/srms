<?php

$config['sidebar.menu']['messages'] = [
	'desktop' => [
		'title' => '消息中心',
		'icon' => '!messages/icons/48/messages.png',
		'url' => '!messages',
		'notif_callback' => 'Message::notif_callback',
	],
	'icon' => [
		'title' => '消息中心',
		'icon' => '!messages/icons/32/messages.png',
		'url' => '!messages',
		'notif_callback' => 'Message::notif_callback',
	],
	'list'=>[
		'title' => '消息中心',
		'class'=>'icon-message1',
		'url' => '!messages',
		'notif_callback' => 'Message::notif_callback',
	],
];
