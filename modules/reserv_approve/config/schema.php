<?php

$config['reserv_approve'] = [
    'fields' => [
        /* 操作用户 */
        'user'=>['type'=>'object','oname'=>'user'],
        /* 关联的预约 */
        'reserv'=>['type'=>'object','oname'=>'eq_reserv'],
        /* 审核状态 */
        'status'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],

        'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes' => [
        'user'=>['fields'=>['user']],
        'reserv'=>['fields'=>['reserv']],
        'status'=>['fields'=>['status']],
        'ctime'=>['fields'=>['ctime']],
    ],
];

$config['abandon_reserv'] = [
    'fields' => [
        /* 记录删除后预约信息 */
        'old_id'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'equipment'=>['type'=>'object','oname'=>'equipment'],
        'user'=>['type'=>'object','oname'=>'user'],
        'dtstart'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'dtend'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'approve_status' => ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
        'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes' => [
        'old_id'=>['fields'=>['old_id']],
        'equipment'=>['fields'=>['equipment']],
        'user'=>['fields'=>['user']],
        'dtstart'=>['fields'=>['dtstart']],
        'dtend'=>['fields'=>['dtend']],
        'ctime'=>['fields'=>['ctime']],
        'approve_status'=>['fields'=>['approve_status']],
    ],
];

$config['eq_reserv']['fields']['approve_status'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['eq_reserv']['indexes']['approve_status'] = ['fields'=>['approve_status']];
