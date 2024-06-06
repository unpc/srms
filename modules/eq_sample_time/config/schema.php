<?php
$config['eq_sample_time'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'ltstart' => ['type' => 'int', 'default' => 0, 'null' => false],
        'ltend' => ['type' => 'int', 'default' => 0, 'null' => false],
        'dtstart' => ['type' => 'int', 'default' => 0, 'null' => false],
        'dtend' => ['type' => 'int', 'default' => 0, 'null' => false],
        'type' => ['type' => 'int', 'default' => 0, 'null' => false],
        'num' => ['type' => 'int', 'default' => 0, 'null' => false],
        'days' => ['type' => 'text', 'null' => true],
        'uncontroluser' => ['type' => 'text', 'null' => true],
        'uncontrollab' => ['type' => 'text', 'null' => true],
        'uncontrolgroup' => ['type' => 'text', 'null' => true],
        'uncontrolall' => ['type' => 'tinyint', 'default' => 1, 'null' => false], // 默认为所有用户都可以使用该工作时间
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
    ],
];
