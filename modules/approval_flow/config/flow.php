<?php

$config['eq_reserv'] = [
    'approve_pi' => [
        'title' => '待PI审',
        'action' => [
            'pass' => [
                'title' => '通过',
                'next' => 'approve_incharge'
            ],
            'reject' => [
                'title' => '驳回',
                'next' => 'rejected'
            ],
        ]
    ],
    'approve_incharge' => [
        'title' => '待机主审',
        'action' => [
            'pass' => [
                'title' => '通过',
                'next' => 'done'
            ],
            'reject' => [
                'title' => '驳回',
                'next' => 'rejected'
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

$config['eq_sample'] = [
    'approve_pi' => [
        'title' => '待PI审',
        'action' => [
            'pass' => [
                'title' => '通过',
                'next' => 'approve_incharge'
            ],
            'reject' => [
                'title' => '驳回',
                'next' => 'rejected'
            ],
        ]
    ],
    'approve_incharge' => [
        'title' => '待机主审',
        'action' => [
            'pass' => [
                'title' => '通过',
                'next' => 'done'
            ],
            'reject' => [
                'title' => '驳回',
                'next' => 'rejected'
            ],
        ]
    ],
    'done' => [
        'title' => '已通过'
    ],
    'rejected' => [
        'title' => '已驳回'
    ]
];

$config['ue_training'] = [
    'approve_incharge' => [
        'title' => '待机主审',
        'action' => [
            'pass' => [
                'title' => '通过',
                'next' => 'done'
            ],
            'reject' => [
                'title' => '驳回',
                'next' => 'rejected'
            ],
        ]
    ],
    'done' => [
        'title' => '已通过'
    ],
    'rejected' => [
        'title' => '已驳回'
    ]
];

$config['me_reserv'] = [
    'approve_incharge' => [
        'title' => '待负责人审',
        'action' => [
            'pass' => [
                'title' => '通过',
                'next' => 'done'
            ],
            'reject' => [
                'title' => '驳回',
                'next' => 'rejected'
            ],
        ]
    ],
    'done' => [
        'title' => '已通过'
    ],
    'rejected' => [
        'title' => '已驳回'
    ]
];