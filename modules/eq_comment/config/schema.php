<?php

// 机主对使用者进行的评价 其中冗余字段是为了方便进行搜索筛选
$config['eq_comment_user'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'source' => ['type' => 'object'],
        'user' => ['type' => 'object', 'oname' => 'user'], // 使用者
        'commentator' => ['type' => 'object', 'oname' => 'user'], // 评价者
        'user_attitude' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 样品吻合度
        'user_proficiency' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 熟练度
        'user_cleanliness' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 清洁度/标准操作
        'test_importance' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 重要性
        'test_dtstart' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 测试开始时间
        'test_dtend' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 测试结束时间
        'test_purpose' => ['type' => 'varchar(255)', 'null' => FALSE, 'default' => ''], // 测试目的
        'test_method' => ['type' => 'varchar(255)', 'null' => FALSE, 'default' => ''], // 测试方法
        'test_result' => ['type' => 'varchar(255)', 'null' => FALSE, 'default' => ''], // 测试结果
        'test_fit' => ['type' => 'varchar(255)', 'null' => FALSE, 'default' => ''], // 吻合度
        'test_understanding' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 设备了解度
        'test_remark' => ['type' => 'text', 'null' => FALSE, 'default' => ''], // 备注
	],
	'indexes' => [
		'equipment' => ['fields' => ['equipment']],
		'source' => ['fields' => ['source']],
		'user_attitude' => ['fields' => ['user_attitude']],
		'test_dtstart' => ['fields' => ['test_dtstart']],
		'test_dtend' => ['fields' => ['test_dtend']],
	],
];

// 使用者对机主进行的评价
$config['eq_comment_incharge'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'source' => ['type' => 'object'],
        'user' => ['type' => 'object', 'oname' => 'user'], // 使用者
        'service_attitude' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 服务态度
        'service_quality' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 服务质量
        'technical_ability' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 技术能力
        'emergency_capability' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 应急能力
        'detection_performance' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 检测性能
        'accuracy' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 准确性
        'compliance' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 吻合度
        'timeliness' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 及时性
        'sample_processing' => ['type' => 'int', 'null' => FALSE, 'default' => 5], // 样品处理
        'comment_suggestion' => ['type' => 'text', 'null' => FALSE, 'default' => ''], // 评价建议
        'obj_dtstart' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 冗余使用记录或送样记录的开始时间
        'obj_dtend' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 冗余使用记录或送样记录的结束时间
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
        'source' => ['fields' => ['source']],
        'user' => ['fields' => ['user']],
        'service_attitude' => ['fields' => ['service_attitude']],
        'obj_dtstart' => ['fields' => ['obj_dtstart']],
		'obj_dtend' => ['fields' => ['obj_dtend']],
    ],
];

$config['eq_sample']['fields']['feedback'] = ['type' => 'tinyint', 'null' => FALSE, 'default' => 0];
$config['eq_sample']['indexes']['feedback'] = ['fields'=>['feedback']];
