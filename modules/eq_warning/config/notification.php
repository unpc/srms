<?php

$config['handlers']['modal'] = [
    'class'=>'Notification_Modal',
    'text'=>'通过系统提示弹框',
    'module_name'=>'eq_warning',
    'name'=>'',
    'default_send'=> false,
    'default_receive'=> true,
];


$config['eq_warning.less_use'] = [
    'description'=>'仪器未达到额定使用机时预警设置',
    'title'=>'预警: 仪器未达到额定使用机时预警',
    'body'=>'%user, 您好:\n\n仪器(ID:%equipment_id) %equipment\,未达到额定使用机时%time,请合理安排使用时间',
    'i18n_module' => 'eq_warning',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%time'=>'时长(单位:小时)',
        '%equipment_id'=>'仪器ID',
    ],
    'send_by'=>[
        'messages' => ['通过消息中心发送', 1],
        'modal' => ['通过系统提示弹框', 0],
    ]
];

$config['eq_warning.more_use'] = [
    'description'=>'超过最大使用机时预警设置',
    'title'=>'预警: 超过最大使用机时预警',
    'body'=>'%user, 您好:\n\n仪器(ID:%equipment_id) %equipment\已超过最大使用机时%time,请合理安排使用时间',
    'i18n_module' => 'eq_warning',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%time'=>'时长(单位:小时)',
        '%equipment_id'=>'仪器ID',
    ],
    'send_by'=>[
        'messages' => ['通过消息中心发送', 1],
        'modal' => ['通过系统提示弹框', 0],
    ]
];

$config['eq_warning.too_less_use'] = [
    'description'=>'低于最小使用机时预警设置',
    'title'=>'预警: 低于最小使用机时预警',
    'body'=>'%user, 您好:\n\n仪器(ID:%equipment_id) %equipment\低于最小使用机时%time,请合理安排使用时间',
    'i18n_module' => 'eq_warning',
    'strtr'=>[
        '%equipment' =>'仪器名称',
        '%user'=>'用户名称',
        '%time'=>'时长(单位:小时)',
        '%equipment_id'=>'仪器ID',
    ],
    'send_by'=>[
        'messages' => ['通过消息中心发送', 1],
        'modal' => ['通过系统提示弹框', 0],
    ]
];

// $config['eq_warning.offline'] = [
//     'description'=>'仪器离线预警',
//     'title'=>'预警: 仪器离线预警',
//     'body'=>'%user, 您好:\n\n你负责的仪器(ID:%equipment_id) %equipment 于%time断开与服务器的链接,请及时检查网络环境。',
//     'i18n_module' => 'eq_warning',
//     'strtr'=>[
//         '%equipment' =>'仪器名称',
//         '%user'=>'用户名称',
//         '%time'=>'时间',
//         '%equipment_id'=>'仪器ID',
//     ],
//     'send_by'=>[
//         'messages' => ['通过消息中心发送', 1],
//         'modal' => ['通过系统提示弹框', 0],
//     ]
// ];
