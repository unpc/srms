<?php

$config['eq_maintain'] = [
    'fields' => [
        'equipment' => ['type'=>'object', 'oname' => 'equipment'],
        'time' => ['type'=>'int','null'=>FALSE,'default'=>0],
        'type' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'amount' => ['type'=>'double', 'null'=>FALSE, 'default' => 0],
        'description' => ['type' => 'varchar(500)', 'null' => FALSE, 'default' => ''],
        'm_amount' => ['type'=>'double', 'null'=>FALSE, 'default' => 0],//维修费
        'm_fund' => ['type'=>'double', 'null'=>FALSE, 'default' => 0],//维修开放基金
        'm_income' => ['type'=>'double', 'null'=>FALSE, 'default' => 0],//对外开放共享收入
        'm_outlay' => ['type'=>'double', 'null'=>FALSE, 'default' => 0],//其他途径维修经费
        'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes' => [
        'equipment'=>['fields'=>['equipment']],
        'time'=>['fields'=>['time']],
        'type' => ['fields' => ['type']],
        'amount' => ['fields' => ['amount']],
        'ctime'=>['fields'=>['ctime']]
    ],
];

$config['eq_keep'] = [
    'fields' => [
        'equipment' => ['type'=>'object', 'oname' => 'equipment'],
        'time' => ['type'=>'int','null'=>FALSE,'default'=>0],
        'amount' => ['type'=>'double', 'null'=>FALSE, 'default' => 0],
        'rate' => ['type'=>'double', 'null'=>FALSE, 'default' => 0], // 学校资助比例
        'description' => ['type' => 'varchar(500)', 'null' => FALSE, 'default' => ''],
        'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes' => [
        'equipment'=>['fields'=>['equipment']],
        'time'=>['fields'=>['time']],
        'amount' => ['fields' => ['amount']],
        'ctime'=>['fields'=>['ctime']]
    ],
];
