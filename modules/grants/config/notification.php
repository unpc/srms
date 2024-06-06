<?php

$config['grants_admin.content'][] = 'notification.grants.over_remind_time';
$config['grants_admin.content'][] = 'notification.grants.near_remind_time';

$config['grants.over_remind_time'] = [
        'description'=>'设置用户收到经费过期的提醒消息',
        'title'=>'警告: 经费 [%grant_project] 已过期!',
        'body'=>'%user, 您好: \n\n您的实验室经费 [%grant_project] 今天已过期, 请及时处理!',
        'i18n_module'=>'grants',
        'strtr'=>[
            '%user'=> '用户姓名',
            '%grant_project'=> '经费名称'
            ],
        'send_by'=>[
            'email' => ['通过电子邮件发送', 0],
            'messages' => ['通过消息中心发送', 1],
            ]
        ];

$config['grants.near_remind_time'] = [
        'description'=>'设置用户收到经费即将过期的提醒消息',
        'title'=>'警告: 经费 [%grant_project] 即将过期!',
        'body'=>'%user, 您好: \n\n您的实验室经费 [%grant_project] 将在 %date 过期, 请及时处理!',
        'i18n_module'=>'grants',
        'strtr'=>[
            '%user'=> '用户姓名',
            '%grant_project'=> '经费名称',
            '%date'=> '到期日期'
            ],
        'send_by'=>[
            'email' => ['通过电子邮件发送', 0],
            'messages' => ['通过消息中心发送', 1],
            ]
        ];
