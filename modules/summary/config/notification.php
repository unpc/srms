<?php

$config['report.message'] = [
    'description' => '科技部任务申报提醒',
    'title'       => '国家科技部任务申报发布提醒',
    'body'        => '%username, 您好! \n 国家科技部申报任务: %report_title 已经发布\n 填报时间: %fill_dtstart ~ %fill_dtend\n请您注意任务进度，及时进行填报或审核. \n\n祝您: 工作顺利, 心情愉快! \n\n',
    'i18n_module' => 'summary',
    'strtr'       => [
        '%username'     => '用户姓名',
        '%fill_dtstart' => '开始填报时间',
        '%username'     => '结束填报时间',
        '%report_title' => '申报任务名称',
    ],
    'send_by'     => [
        'email'    => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];
