<?php
$config['people.info.short.picture'][] = 'Message::short_picture_of_people';
$config['module[messages].is_accessible'][] = 'Message::is_accessible';
$config['newsletter.get_contents[extra]'][] = 'Message::message_newsletter_content';

// 个人门户对接hook
$config['application.component.views'][] = 'Message_Com::views';
$config['application.component.view.unRead'][] = 'Message_Com::view_unRead';

$config['message.api.v1.list.GET'][] = 'Message_API::messages_get';
$config['message.api.v1.PATCH'][] = 'Message_API::message_patch';

$config['message.api.v1.listx.GET'][] = 'API_Messages::getList';
$config['message.api.v1.read.POST'][] = 'API_Messages::message_patch';
$config['message.api.v1.data.GET'][] = 'API_Messages::getData';
$config['message.api.v1.messages.DELETE'][] = 'API_Messages::deleteMessage';
$config['message.api.v1.reply.POST'][] = 'API_Messages::replyMessage';
$config['message.api.v1.send.POST'][] = 'API_Messages::sendMessage';