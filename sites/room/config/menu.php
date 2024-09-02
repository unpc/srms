<?php

$config['top'] = [
    'space' => [
        'title' => '空间管理',
        'icon' => 'iconfont icon-home',
        'items' => [
            'meeting' => [
                'title' => '空间列表',
                'url' => '!meeting',
                'icon' => ''
            ]
        ]
    ],
    'attendance' => [
        'title' => '考勤管理',
        'icon' => 'iconfont icon-bpm',
        'items' => [
            'attendance' => [
                'title' => '考勤列表',
                'url' => '!attendance',
                'icon' => ''
            ]
        ]
    ],
    'course' => [
        'title' => '课程管理',
        'icon' => 'iconfont icon-deduction',
        'items' => [
            'course' => [
                'title' => '课程列表',
                'url' => '!course',
                'icon' => ''
            ]
        ]
    ],
    'exam' => [
        'title' => '考场管理',
        'icon' => 'iconfont icon-exam',
        'items' => [
            'exam' => [
                'title' => '考场信息',
                'url' => '!exam',
                'icon' => ''
            ]
        ]
    ],
    'notice' => [
        'title' => '信息发布',
        'icon' => 'iconfont icon-message1',
        'items' => [
            'notice' => [
                'title' => '公播信息',
                'url' => '!notice',
                'icon' => ''
            ]
        ]
    ],
    'safe' => [
        'title' => '安全管理',
        'icon' => 'iconfont icon-rule-manage',
        'items' => [
            'entrance' => [
                'title' => '门禁管理',
                'url' => '!entrance',
                'icon' => ''
            ]
        ]
    ],
    'data' => [
        'title' => '数据分析',
        'icon' => 'iconfont icon-data',
        'items' => [
            'space' => [
                'title' => '空间数据分析',
                'url' => '!data/space',
                'icon' => ''
            ]
        ]
    ],
    'system' => [
        'title' => '系统设置',
        'icon' => 'iconfont icon-setting1',
        'items' => [
            'people' => [
                'title' => '人员和权限',
                'icon' => 'iconfont icon-user3',
                'items' => [
                    'people' => [
                        'title' => '人员列表',
                        'url' => '!people',
                        'icon' => ''
                    ],
                    'labs' => [
                        'title' => '人员分组',
                        'url' => '!labs',
                        'icon' => ''
                    ],
                    'role' => [
                        'title' => '权限管理',
                        'url' => '!roles',
                        'icon' => ''
                    ]
                ]
            ],
            'credit' => [
                'title' => '信用管理',
                'icon' => 'iconfont icon-credit',
                'items' => [
                    'index' => [
                        'title' => '成员信用',
                        'url' => '!credit/index',
                        'icon' => ''
                    ],
                    'record' => [
                        'title' => '信用明细',
                        'url' => '!credit/credit_record',
                        'icon' => ''
                    ],
                    'ban' => [
                        'title' => '黑名单',
                        'url' => '!credit/ban',
                        'icon' => ''
                    ]
                ]
            ],
            'setting' => [
                'title' => '基础设置',
                'icon' => 'iconfont icon-setting',
                'items' => [
                    'group' => [
                        'title' => '组织机构',
                        'url' => 'admin/groups',
                        'icon' => ''
                    ],
                    'location' => [
                        'title' => '位置管理',
                        'url' => 'admin/locations',
                        'icon' => ''
                    ],
                    'client' => [
                        'title' => '终端管理',
                        'url' => '!client',
                        'icon' => ''
                    ]
                ]
            ]
        ]
    ]
];