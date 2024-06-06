<?php
$config['classification']['user']["orders\004订单相关消息提醒"][] = 'orders.order_confirmed';
$config['classification']['user']["orders\004订单相关消息提醒"][] = 'orders.order_canceled';
$config['classification']['user']["orders\004订单相关消息提醒"][] = 'orders.order_received';

//申购订出后向申请者发送消息
$config['orders.order_confirmed'] = [
    'description'=>'设置用户添加申购后的提醒消息',
    'title'=>'提醒: 您的订单申购已订出! ',
    'body'=>'%user, 您好: \n\n您的订单%order已经订出! ',
    'i18n_module'=>'orders',
    'strtr'=>[
        '%user'=> '用户姓名',
        '%order'=> '申购订单名称'
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

//申购被管理员取消后向申请者发送消息, 自己取消自己的申购不会发送消息
$config['orders.order_canceled'] = [
    'description'=>'设置用户订单申购被管理员取消的提醒消息',
    'title'=>'提醒: 您的订单申购已取消! ',
    'body'=>'%user, 您好: \n\n您的订单%order由于下列原因, 已被%incharge取消! \n%reason',
    'i18n_module'=>'orders',
    'strtr'=>[   
        '%user'=> '用户姓名',
        '%order'=> '申购订单名称',
        '%incharge'=>'管理员姓名',
        '%reason'=> '订单被取消的原因',
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

//申购到货后向申请者发送消息
$config['orders.order_received'] = [
    'description'=>'设置用户订单申购到货后的提醒消息',
    'title'=>'提醒: 您的订单申购已经到货! ',
    'body'=>'%user, 您好: \n\n您订购的%order已经到货! ',
    'i18n_module'=>'orders',
    'strtr'=>[
        '%user'=> '用户姓名',
        '%order'=> '申购订单名称'
    ],
    'send_by'=>[
        'email' => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];   
