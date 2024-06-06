<?php

$config['classification']['user']['信用分相关消息提醒'][] = 'credit.credit_deduction';
$config['classification']['user']['信用分相关消息提醒'][] = 'credit.credit_increase';
$config['classification']['user']['信用分相关消息提醒'][] = 'credit.unactive_user';
$config['classification']['user']['信用分相关消息提醒'][] = 'credit.can_not_reserv';
$config['classification']['user']['信用分相关消息提醒'][] = 'credit.ban';
$config['classification']['user']['信用分相关消息提醒'][] = 'credit.send_msg';
$config['classification']['user']['信用分相关消息提醒'][] = 'credit.eq_ban';
$config['classification']['user']['信用分相关消息提醒'][] = 'credit.lab_ban';

$config['credit.credit_deduction'] = [
    'description' => '个人信用分扣除通知',
    'title'       => '您的个人信用分被扣除!',
    'body'        => '%user, 您好: \n\n您的个人信用分于%time被扣除%score分,\n扣分原因: %reason,\n\n%measures请注意规范使用!',
    'strtr'       => [
        '%user'     => '用户姓名',
        '%time'     => '积分变更时间',
        '%score'    => '分数',
        '%reason'   => '扣分原因',
        '%measures' => '惩罚说明',
    ],
    'send_by'     => [
        'email'    => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

$config['credit.credit_increase'] = [
    'description' => '个人信用分增加通知',
    'title'       => '您有新的奖励信用分!',
    'body'        => '%user, 您好: \n\n您的个人信用分于%time增加%score分,\n奖励原因: %reason,\n\n加油! 请继续保持规范使用的好习惯!',
    'strtr'       => [
        '%user'   => '用户姓名',
        '%time'   => '积分变更时间',
        '%score'  => '分数',
        '%reason' => '加分原因',
    ],
    'send_by'     => [
        'email'    => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

$config['credit.send_msg'] = [
    'description' => '信用过低即将接近封禁阈值的警告',
    'title'       => '警告: 您的信用分过低, 请注意规范使用!',
    'body'        => '%user, 您好: \n\n您的个人信用分过低(当前信用分%number)已临近封禁阈值, \n\n%measures请注意规范使用!',
    'strtr'       => [
        '%user'         => '用户姓名',
        '%number'       => '当前信用分',
        '%measures'     => '惩罚说明',
    ],
    'send_by'     => [
        'email'    => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
    'receive_by'=> [
        'messages'=> TRUE,
    ],
];

$config['credit.unactive_user'] = [
    'description' => '信用分过低账号变为未激活的通知',
    'title'       => '通知: 您的账号因信用分过低已失效!',
    'body'        => '%user, 您好: \n\n您的信用分过低, 已达系统封禁阈值, 当前登录账号失效.\n当前信用分: %number,\n封禁阈值:%banned_score,\n\n如有疑问,请联系设备处说明原因',
    'strtr'       => [
        '%user'         => '用户姓名',
        '%number'       => '当前信用分',
        '%banned_score' => '封禁阈值分数',
        '%measures'     => '惩罚措施',
    ],
    'send_by'     => [
        'email'    => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
    'receive_by'=> [
        'messages'=> TRUE,
    ],
];

$config['credit.can_not_reserv'] = [
    'description' => '信用分过低账号禁止预约的通知',
    'title'       => '通知: 您的账号因信用分过低已被禁止预约使用系统内仪器!',
    'body'        => '%user, 您好: \n\n您的信用分过低, 已达系统禁止预约仪器的封禁阀值, 您可至个人信用记录中查看本人信用详情. \n(注: 当前账号仍可进行仪器送样操作. )\n当前信用分: %number,\n封禁阈值:%banned_score,\n\n如有疑问,请联系设备处说明原因. ',
    'strtr'       => [
        '%user'         => '用户姓名',
        '%number'       => '当前信用分',
        '%banned_score' => '封禁阈值分数',
        '%measures'     => '惩罚措施',
    ],
    'send_by'     => [
        'email'    => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
    'receive_by'=> [
        'messages'=> TRUE,
    ],
];

$config['credit.ban'] = [
    'description' => '信用分过低自动加入黑名单的通知',
    'title'       => '通知: 您的账号因信用分过低已被加入系统黑名单!',
    'body'        => '%user, 您好: \n\n您的信用分过低, 已达系统黑名单禁阀值, 当前登录账号将无法再进行仪器预约、送样和使用, 您可至个人信用记录中查看本人信用详情. \n当前信用分: %number,\n加入黑名单分值:%banned_score,\n\n如有疑问,请联系设备处说明原因. ',
    'strtr'       => [
        '%user'         => '用户姓名',
        '%number'       => '当前信用分',
        '%banned_score' => '封禁阈值分数',
        '%measures'     => '惩罚措施',
    ],
    'send_by'     => [
        'email'    => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
    'receive_by'=> [
        'messages'=> TRUE,
    ],
];

$config['credit.thaw'] = [
    'description' => '账号解禁的的消息提醒',
    'title'       => '通知: 您的账号已解禁!',
    'body'        => '%user, 您好: \n\n因 %admin 于 %time 执行解禁操作, 当前登录已恢复正常使用.\n\n如有疑问,请联系设备处说明原因',
    'strtr'       => [
        '%user'  => '用户姓名',
        '%admin' => '解禁人',
        '%time'  => '解禁时间',
    ],
    'send_by'     => [
        'email'    => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
];

$config['credit.eq_ban'] = [
    'description' => '触发其他限制规则自动加入黑名单的通知',
    'title'       => '通知: 您的账号因%reason已被加入%scope!',
    'body'        => '%user, 您好:\n\n 您因%reason已达%scope禁阀值, 当前登录账号将无法再进行仪器预约、送样和使用。 \n\n如有疑问,请联系设备处说明原因。',
    'strtr'       => [
        '%user'   => '用户姓名',
        '%reason' => '违规原因（多次触发单台仪器扣分项/同时存在于多台仪器黑名单）',
        '%scope'  => '系统黑名单/仪器黑名单',
    ],
    'send_by'     => [
        'email'    => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
    'receive_by'=> [
        'messages'=> TRUE,
    ],
];
$config['credit.lab_ban'] = [
    'description' => '触发其他限制规则, 课题组自动加入黑名单的通知',
    'title'       => '通知: 您的账号因同课题组多人被加入系统黑名单, 导致全组均被加入系统黑名单!',
    'body'        => '%user, 您好:\n\n您因“同课题组多人被加入系统黑名单”已达全组封禁的禁阀值, 当前登录账号将无法再进行仪器预约、送样和使用。 \n\n如有疑问,请联系设备处说明原因.',
    'strtr'       => [
        '%user'   => '用户姓名',
    ],
    'send_by'     => [
        'email'    => ['通过电子邮件发送', 1],
        'messages' => ['通过消息中心发送', 1],
    ],
    'receive_by'=> [
        'messages'=> TRUE,
    ],
];

$config['equipments_conf'][] = 'notification.eq_reserv.violation.exceed_preset';