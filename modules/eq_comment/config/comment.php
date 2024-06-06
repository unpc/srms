<?php

$config['rate']['tip'] = ['非常差', '差', '一般', '好', '非常好'];

$config['export_columns.incharge'] = [
    'equipment' => '仪器名称',
    'user' => '使用者',
    'service_attitude' => '服务态度',
    'service_quality' => '服务质量',
    'technical_ability' => '技术能力',
    'emergency_capability' => '应急能力',
    'detection_performance' => '检测性能',
    'accuracy' => '准确性',
    'compliance' => '吻合度',
    'timeliness' => '及时性',
    'sample_processing' => '样品处理',
    'comment_suggestion' => '评价建议',
];

$config['export_columns.user'] = [
    'equipment' => '仪器名称',
    'commentator' => '评价者',
    'user_attitude' => '样品吻合度',
    'user_proficiency' => '熟练度',
    'test_understanding' => '设备了解度',
    'user_cleanliness' => '清洁度 / 标准操作',
    'test_importance' => '重要性',
    'test_purpose' => '测试目的',
    'test_method' => '测试方法',
    'test_result' => '测试结果',
    'test_fit' => '预期吻合度',
    'test_remark' => '备注',
];

$config['comment_columns.incharge'] = [
    '仪器管理员服务评价' => [
        'service_attitude' => [
            'type' => Extra_Model::TYPE_STAR,
            'title' => '服务态度',
            'required' => 1,
            'adopted' => null,
            'params' => null,
            'default' => 1,
            'default_value' => 5
        ],
        'service_quality' => [
            'type' => Extra_Model::TYPE_STAR,
            'title' => '服务质量',
            'required' => 1,
            'adopted' => null,
            'params' => null,
            'default' => 1,
            'default_value' => 5
        ],
        'technical_ability' => [
            'type' => Extra_Model::TYPE_STAR,
            'title' => '技术能力',
            'required' => 1,
            'adopted' => null,
            'params' => null,
            'default' => 1,
            'default_value' => 5
        ],
        'emergency_capability' => [
            'type' => Extra_Model::TYPE_STAR,
            'title' => '应急处理能力',
            'required' => 1,
            'adopted' => null,
            'params' => null,
            'default' => 1,
            'default_value' => 5
        ],
    ],
    '仪器性能评价' => [
        'detection_performance' => [
            'type' => Extra_Model::TYPE_STAR,
            'title' => '检测性能',
            'required' => 1,
            'adopted' => null,
            'params' => null,
            'default' => 1,
            'default_value' => 5
        ]
    ],
    '检测结果评价' => [
        'accuracy' => [
            'type' => Extra_Model::TYPE_STAR,
            'title' => '准确性',
            'required' => 1,
            'adopted' => null,
            'params' => null,
            'default' => 1,
            'default_value' => 5
        ],
        'compliance' => [
            'type' => Extra_Model::TYPE_STAR,
            'title' => '预期目标吻合度',
            'required' => 1,
            'adopted' => null,
            'params' => null,
            'default' => 1,
            'default_value' => 5
        ],
        'timeliness' => [
            'type' => Extra_Model::TYPE_STAR,
            'title' => '测试及时性',
            'required' => 1,
            'adopted' => null,
            'params' => null,
            'default' => 1,
            'default_value' => 5
        ],
        'sample_processing' => [
            'type' => Extra_Model::TYPE_STAR,
            'title' => '样品的保管与处理',
            'required' => 1,
            'adopted' => null,
            'params' => null,
            'default' => 1,
            'default_value' => 5
        ],
        'comment_suggestion' => [
            'type' => Extra_Model::TYPE_TEXTAREA,
            'title' => '服务评价及建议',
            'required' => 0,
            'adopted' => null,
            'params' => null,
            'default' => '',
            'default_value' => ''
        ],
    ],
];