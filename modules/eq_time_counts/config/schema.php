<?php
$config['eq_time_counts'] = [
    'fields' => [
        'equipment' => ['type'=>'object', 'oname' => 'equipment'],
        'ltstart' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'ltend' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'dtstart' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'dtend' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'type' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'num' => ['type' => 'int', 'default' => 0, 'null' => FALSE],
        'days' => ['type'=>'text', 'null'=>TRUE],
        'controlall' => ['type' => 'tinyint', 'default' => 1, 'null'=>FALSE], // 默认为所有用户都可以使用该工作时间
        'controluser' => ['type'=>'text', 'null'=>TRUE],
        'controllab' => ['type'=>'text', 'null'=>TRUE],
        'controlgroup' => ['type'=>'text', 'null'=>TRUE],
        'per_reserv_time'	=>	['type'=>'varchar(20)', 'null'=>FALSE, 'default'=>''],
        'total_reserv_time' => ['type'=>'varchar(20)', 'null'=>FALSE, 'default'=>''],
        'total_reserv_counts' => ['type'=>'varchar(20)', 'null'=>FALSE, 'default'=>''],
    ],
    'indexes' => [
        'equipment' => ['fields'=>['equipment']],
    ],
];
