<?php

$config['eq_reserv'] = [
    'approve' => [
        'title' => '申请中',
        'role' => 'incharge',
        'action' => [
            'pass' => [
                'title' => '通过',
                'next' => 'done',
                'icon' =>'icon-selected'
            ],
            'reject' => [
                'title' => '驳回',
                'next' => 'rejected',
                'icon' =>'icon-cancel'
            ],
        ]
    ],
    'done' => [
        'title' => '已通过'
    ],
    'rejected' => [
        'title' => '已驳回'
    ],
    'expired' => [
        'title' => '已过期'
    ]
];