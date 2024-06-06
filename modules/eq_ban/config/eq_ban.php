<?php
$config['photo.msg.model'] = [
    'description' => '用户图像被上传更新提示',
    'body' => '%subject 于 %date 更新了用户 %user 的头像',
    'strtr' => [
        '%subject' => '更新者',
        '%user' => '被更新者',
        '%date' => '时间',
    ],

];

$config['export_ban'] = [
    'name' => '姓名',
    'reason' => '封禁原因',
    'ctime' => '封禁时间',
    'atime' => '到期时间'
];
$config['export_ban_unseal'] = [
    'name' => '姓名',
    'unsealing_user' => '解封操作人',
    'unsealing_ctime' => '解封时间',
    'lab' => '实验室',
    'reason' => '封禁原因',
    'ctime' => '封禁时间',
    'atime' => '到期时间'
];

$config['export_violation'] = [
    'name' => '姓名',
    'total' => '违规总次数',
    'late' => '迟到次数',
    'leave_early' => '早退次数',
    'overtime' => '超时次数',
    'miss' => '爽约次数'

];