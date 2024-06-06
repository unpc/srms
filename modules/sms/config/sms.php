<?php

$config['api.url'] = 'http://tempurl.org/api';

//消息发送请求接口,默认。站点下单独配置
$config['url'] = 'http://sms-api.luosimao.com/v1/send.json';
//处理器
$config['provider'] = 'common';
//单次可发送短信条数，0为不限制。eg:设置100，代表一次接口调用只允许发给100个手机号
$config['per_limit'] = 0;

//消息中心是否开启短信发送
$config['message_send_by_sms'] = false;
