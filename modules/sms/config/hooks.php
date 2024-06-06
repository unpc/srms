<?php

$config['people.profile.edit.message.form'][] = 'SM_Service::edit_message_form';
$config['people.profile.edit.message.submit'][] = 'SM_Service::edit_message_submit';

//开启消息中心短信发送
$config['message.send.way.view'][] = 'SM_Service::message_send_way_view';
$config['message.send.way.submit'][] = 'SM_Service::message_send_way_submit';