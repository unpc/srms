<?php

$config['equipment'] = [
    'name' => '仪器相关',
    'items' => [
        'eq_charge' => [
            'subname' => '收费设置',
            'require_module' => 'eq_charge',
            'subitems' => [
                'incharges_fee' => [
                    'name'=>'全部用户均收费(alpha)',
                ],
                'foul_charge' => [
                    'name' => '爽约违规计费'
                ],
            ],
        ],
        'eq_record' => [
            'subname' => '使用记录',
            'subitems' => [
                'transaction_locked_deadline' => [
                    'name'=>'计费流水锁定时间',
                    'type'=>'date'
                ],
                'must_connect_lab_project' => [
                    'name'=>'使用记录关联项目必填',
                ],
                'must_samples' => [
                    'name'=>'使用记录反馈样品数默认为空(需勾选反馈显示样品数)',
                ],
                'charge_desc' => [
                    'name'=>'使用收费金额备注', 
                ],
                'duty_teacher' => [
                    'name'=>'可选择值班老师 (值班老师来自仪器负责人)',
                ]
            ],
        ],
        'eq_reserv' => [
            'subname' => '预约设置',
            'require_module' => 'eq_reserv',
            'subitems' => [
                'glogon_arrival' => [
                    'name'=>'Glogon预约结束提醒(alpha)',
                ],
                'glogon_safe' => [
                    'name'=>'Glogon登陆安全提示(alpha)',
                ],
                'must_connect_lab_project' => [
                    'name'=>'预约记录关联项目必填',
                ],
                'delete_edit_remark' => [
                    'name'=>'删除/编辑预约填写理由',
                ],
                'single_equipemnt_reserv' => [
                    'name' => '用户同一时段只能预约同一仪器'
                ],
                'add_ignore_reserv_time' => [
                    'name'=>'管理员可以在非工作时间为用户添加预约',
                ],
            ]
        ],
        'eq_sample' => [
            'subname' => '送样设置',
            'require_module' => 'eq_sample',
            'subitems' => [
                'response_time' => [
                    'name'=>'增加送样申请时间和响应时间(alpha)',
                ],
                'must_connect_lab_project' => [
                    'name'=>'送样记录关联项目必填',
                ],
                'charge_forecast' => [
                    'name'=>'送样记录收费预估',
                ],
            ],
        ],
        'equipment' => [
			'subname' => '使用反馈',
            'subitems' => [
                'feedback_deadline' => [
                    'name'=>'反馈必填',
                ],
                'feedback_show_samples' => [
                    'name'=>'反馈显示样品数',
                ],
            ],
        ],
        'training' => [
            'subname' => '培训设置',
            'subitems' => [
                'training_period' => [
                    'name' => '培训有效期限制',
                ],
            ],
        ],
        'equipment_use' => [
            'subname' => '使用设置',
            'subitems' => [
                'overtime_limit' => [
                    'name'=>'系统仪器超时限制',
                ],
            ],
        ],
        'equipments' => [
            'subname' => '仪器列表',
            'subitems' => [
                'placed_at_the_top' => [
                    'name'=>'列表仪器置顶',
                ],
            ],
        ]
    ]
];

$config['system'] = [
    'name' => '系统设置',
    'items' => [
        'system' => [
            'subname' => '页面设置',
            'subitems' => [
                'logo' => [
                    'name'=>'系统左上角Logo',
                    'type'=>'image',
                    'tip'=>'为确保显示正常，请上传长方形jpg（校徽+学校名称的logo图，不要单传校徽的正方形jpg）',
                ],
                'login_background_image' => [
                    'name'=>'登录页背景图',
                    'type'=>'image'
                ],
                'login_logo' => [
                    'name'=>'登录页Logo图',
                    'type'=>'image'
                ],
                'header_color' => [
                    'name'=>'顶部颜色',
                    'type'=>'input'
                ],
                'header_height' => [
                    'name'=>'顶部高度',
                    'type'=>'input'
                ],
                'header_font_color' => [
                    'name'=>'顶部字体颜色',
                    'type'=>'input'
                ],
                'footer_email' => [
                    'name'=>'联系邮箱',
                    'type'=>'input'
                ],
                'header_phone2' => [
                    'name'=>'客服电话',
                    'type'=>'input'
                ],
                'page_title' => [
                    'name'=>'页面标题',
                    'type'=>'input'
                ],
                'base_url' => [
                    'name'=>'CLI模式URL（延迟生效）',
                    'type'=>'input'
                ],
                'heartbeat' => [
                    'name'=>'账号保持登录状态（延迟生效）'
                ],
            ],
        ],
        'sidebar' => [
            'subname' => '边栏设置',
            'subitems' => [
                'public' => [
                    'name'=>'前台链接',
                    'type'=>'input'
                ],
            ],
        ],
    ]
];

//$config['preferences'] = [
//    'name' => '偏好设置',
//    'items' => [
//        'preferences' => [
//            'subname' => '使用设置',
//            'subitems' => [
//                'sbmenu_mode' => [
//                    'name'=>'侧边栏模式',
//                    'type'=>'radio',
//                    'params'=>[
//                        'icon' => '图标模式',
//                        'list' => '列表模式',
//                    ]
//                ],
//            ]
//        ]
//    ]
//];
if (!$GLOBALS['preload']['gateway.perm_in_uno']) {
$config['user'] = [
    'name' => '人员管理',
    'items' => [
        'login' => [
            'subname' => '登录安全提示',
            'subitems' => [
                'single_login' => [
                    'name'=>'同账号同一时间单一登录(alpha)',
                ],
            ],
        ],
        'signup_must' => [
            'subname' => '注册必填项设置',
            'subitems' => [
                'group_id' => [
                    'name'=>'注册时组织机构必填',
                ],
            ],
        ],
        'signup_edit_must' => [
            'subname' => '添加/编辑必填项设置',
            'subitems' => [
                'group_id' => [
                    'name'=>'添加/编辑时组织机构必填',
                ],
            ],
        ]
    ]
];
}
$config['online'] = [
    'name' => '在线客服',
    'items' => [
        'online' => [
            'subname' => '客服设置',
            'subitems' => [
                'kf5' => [
                    'name'=>'开启逸创云客服(alpha)',
                ],
            ]
        ]
    ]
];

$config['others'] = [
    'name' => '视频监控',
    'items' => [
        'vidmon' => [
            'require_module' => 'vidmon',
            'subname' => '时间间隔',
            'subitems' => [
                'capture_duration' => [
                    'name'=>'定时截图时间间隔',
                    'type'=>'time',
                    'format'=>'sih'
                ],
                'alarmed_capture_duration' => [
                    'name'=>'报警后截图时间间隔',
                    'type'=>'time',
                    'format'=>'sih'
                ],
                'capture_max_live_time' => [
                    'name'=>'历史记录保存时间',
                    'type'=>'time',
                    'format'=>'dm'
                ]
            ]
        ],
        'stream' => [
            'subname' => '流媒体',
            'subitems' => [
                'use_stream' => [
                    'name' => '视频监控使用流媒体',
                ],
            ]
        ]
    ]
];

$config['i18n'] = [
    'name' => '字段修改',
    'items' => [
        'eq_sample_i18n' => [
            'require_module' => 'eq_sample',
            'subname' => '送样',
            'subitems' => [
                'eq_sample' => [
                    'name'=>'送样',
                    'type'=>'input',
                ],
                // 'eq_sample_r' => [
                //     'name'=>'送样预约',
                //     'type'=>'input',
                // ],
            ]
        ],
        'eq_reserv_i18n' => [
            'require_module' => 'eq_reserv',
            'subname' => '预约',
            'subitems' => [
                'eq_reserv' => [
                    'name'=>'预约',
                    'type'=>'input',
                ],
                // 'eq_reserv_r' => [
                //     'name'=>'仪器预约',
                //     'type'=>'input',
                // ],
            ]
        ],
        'people_i18n' => [
            'subname' => '人员类型',
            'subitems' => [
                'undergraduate' => [
                    'name'=>'本科生',
                    'type'=>'input',
                ],
                'graduate' => [
                    'name'=>'硕士研究生',
                    'type'=>'input',
                ],
                'doctor' => [
                    'name'=>'博士研究生',
                    'type'=>'input',
                ],
                'pi' => [
                    'name'=>'课题负责人(PI)',
                    'type'=>'input',
                ],
                'assistant' => [
                    'name'=>'科研助理',
                    'type'=>'input',
                ],
                'labadmin' => [
                    'name'=>'PI助理/实验室管理员',
                    'type'=>'input',
                ],
                'technician' => [
                    'name'=>'技术员',
                    'type'=>'input',
                ],
                'postdoctoral' => [
                    'name'=>'博士后',
                    'type'=>'input',
                ],
            ]
        ]
    ]
];

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['i18n'] = [
        'name' => '字段修改',
        'items' => [
            'eq_sample_i18n' => [
                'require_module' => 'eq_sample',
                'subname' => '送样',
                'subitems' => [
                    'eq_sample' => [
                        'name'=>'送样',
                        'type'=>'input',
                    ],
                    // 'eq_sample_r' => [
                    //     'name'=>'送样预约',
                    //     'type'=>'input',
                    // ],
                ]
            ],
            'eq_reserv_i18n' => [
                'require_module' => 'eq_reserv',
                'subname' => '预约',
                'subitems' => [
                    'eq_reserv' => [
                        'name'=>'预约',
                        'type'=>'input',
                    ],
                    // 'eq_reserv_r' => [
                    //     'name'=>'仪器预约',
                    //     'type'=>'input',
                    // ],
                ]
            ]
        ]
    ];
}

$config['lab_signup'] = [
    'name' => '课题组注册字段',
    'items' => [
        'outside' => [
            'subname' => '课题组注册字段（保存后会推送勾选字段至UNO，可在UNO配置注册表单）',
            'subitems' => [
                'organization' => [
                    'name'=>'单位名称',
                    'title' => '单位名称',
                    'type'=>'checkbox',
                ],
                'tax_no' => [
                    'name'=>'纳税人识别号',
                    'title' => '纳税人识别号',
                    'type'=>'checkbox',
                ],
                'bill_information_address' => [
                    'name'=>'开票地址',
                    'title' => '开票地址',
                    'type'=>'checkbox',
                ],
                'bill_information_phone' => [
                    'name'=>'开票电话',
                    'title' => '开票电话',
                    'type'=>'checkbox',
                ],
                'bill_bank' => [
                    'name'=>'开户行',
                    'title' => '开户行',
                    'type'=>'checkbox',
                ],
                'bank_account' => [
                    'name'=>'开户行帐号',
                    'title' => '开户行帐号',
                    'type'=>'checkbox',
                ],
            ]
        ],
    ]
];

$config['billing'] = [
    'name' => '财务相关',
    'items' => [
        'billing_center' => [
            'subname' => '财务中心',
            'subitems' => [
                'notification' => [
                    'name'=>'使用结算通知（需要配置系统设置的页面标题，CLI模式URL)',
                ],
            ]
        ]
    ]
];

