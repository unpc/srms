<?php

$config['tag_service_type']['fields']['name'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['tag_service_type']['fields']['name_abbr'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['tag_service_type']['fields']['parent'] = ['type' => 'object', 'oname' => 'tag_service_type'];
$config['tag_service_type']['fields']['root'] = ['type' => 'object', 'oname' => 'tag_service_type'];
$config['tag_service_type']['fields']['readonly'] = ['type' => 'tinyint', 'null' => FALSE, 'default' => 0];
$config['tag_service_type']['fields']['ctime'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['tag_service_type']['fields']['mtime'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['tag_service_type']['fields']['weight'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['tag_service_type']['fields']['code'] = ['type' => 'varchar(150)', 'null' => true];
$config['tag_service_type']['indexes']['name'] = ['fields' => ['name', 'parent'], 'type' => 'unique'];
$config['tag_service_type']['indexes']['parent'] = ['fields' => ['parent']];
$config['tag_service_type']['indexes']['root'] = ['fields' => ['root']];
$config['tag_service_type']['indexes']['ctime'] = ['fields' => ['ctime']];
$config['tag_service_type']['indexes']['mtime'] = ['fields' => ['mtime']];
$config['tag_service_type']['indexes']['weight'] = ['fields' => ['weight']];
$config['tag_service_type']['indexes']['code'] = ['fields' => ['code']];

$config['service_project'] = [
    'fields' => [
        'creator' => ['type' => 'object', 'oname' => 'user'],
        'ref_no' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'name' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'name_abbr' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'ctime' => ['type' => 'int', 'null' => false, 'default' => 0],
    ],
    'indexes' => [
        'creator' => ['fields' => ['creator']],
        'ref_no' => ['fields' => ['ref_no']],
        'name' => ['fields' => ['name']],
        'name_abbr' => ['fields' => ['name_abbr']],
        'ctime' => ['fields' => ['ctime']],
    ],
];

$config['service'] = [
    'fields' => [
        'creator' => ['type' => 'object', 'oname' => 'user'],
        'ref_no' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'name' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'name_abbr' => ['type' => 'varchar(150)', 'null' => false, 'default' => ''],
        'billing_department' => ['type' => 'object', 'oname' => 'billing_department'],
        'service_type' => ['type' => 'object', 'oname' => 'tag_service_type'],
        'group' => ['type' => 'object', 'oname' => 'tag_group'],
        'intervals' => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],//周期
        'description' => ['type' => 'text', 'null' => false, 'default' => ''],//服务简介
        'sample_requires' => ['type' => 'text', 'null' => false, 'default' => ''],//样品要求
        'charge_settings' => ['type' => 'text', 'null' => false, 'default' => ''],//收费标准
        'attentions' => ['type' => 'text', 'null' => false, 'default' => ''],//注意事项
        'phones' => ['type' => 'varchar(64)', 'null' => false, 'default' => ''],//联系电话
        'emails' => ['type' => 'varchar(64)', 'null' => false, 'default' => ''],//联系邮箱
        'ctime' => ['type' => 'int', 'null' => false, 'default' => 0],
    ],
    'indexes' => [
        'ref_no' => ['fields' => ['ref_no']],
        'name' => ['fields' => ['name']],
        'name_abbr' => ['fields' => ['name_abbr']],
        'service_type' => ['fields' => ['service_type']],
        'group' => ['fields' => ['group']],
        'ctime' => ['fields' => ['ctime']],
    ],
];

//服务下项目的仪器
$config['service_equipment'] = [
    'fields' => [
        'service' => ['type' => 'object', 'oname' => 'service'],
        'project' => ['type' => 'object', 'oname' => 'service_project'],
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
    ],
    'indexes' => [
        'service' => ['fields' => ['service']],
        'project' => ['fields' => ['project']],
        'equipment' => ['fields' => ['equipment']],
    ],
];

//服务申请
$config['service_apply'] = [
    'fields' => [
        'service' => ['type' => 'object', 'oname' => 'service'],
        'user' => ['type' => 'object', 'oname' => 'user'],
        'approval_user' => ['type' => 'object', 'oname' => 'user'],
        'approval_time' => ['type' => 'int', 'null' => false, 'default' => 0],
        'ref_no' => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],
        'status' => ['type' => 'int', 'null' => false, 'default' => 0],
        'dtstart' => ['type' => 'int', 'null' => false, 'default' => 0],//开始时间
        'dtend' => ['type' => 'int', 'null' => false, 'default' => 0],//结束时间
        'dtsubmit' => ['type' => 'int', 'null' => false, 'default' => 0],//申请时间
        'amount' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],//总金额
        'dtrequest' => ['type' => 'int', 'null' => false, 'default' => 0],//期望完成时间
        'samples' => ['type' => 'int', 'null' => false, 'default' => 0],//样品数
        'samples_description' => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],//样品描述
        'result' => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],//检测结果
        'ctime' => ['type' => 'int', 'null' => false, 'default' => 0],//创建时间
    ],
    'indexes' => [
        'service' => ['fields' => ['service']],
        'user' => ['fields' => ['user']],
        'ref_no' => ['fields' => ['ref_no']],
        'status' => ['fields' => ['status']],
        'dtstart' => ['fields' => ['dtstart']],
        'amount' => ['fields' => ['amount']],
        'dtend' => ['fields' => ['dtend']],
    ],
];

//申请之后拆分的任务记录，
$config['service_apply_record'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'project' => ['type' => 'object', 'oname' => 'service_project'],
        'apply' => ['type' => 'object', 'oname' => 'service_apply'],
        'service' => ['type' => 'object', 'oname' => 'service'],
        'user' => ['type' => 'object', 'oname' => 'user'],
        'lab' => ['type' => 'object', 'oname' => 'lab'],
        'operator' => ['type' => 'object', 'oname' => 'user'],//最新结束服务的人
        'ref_no' => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],
        'status' => ['type' => 'int', 'null' => false, 'default' => 0],//检测状态
        'dtstart' => ['type' => 'int', 'null' => false, 'default' => 0],//开始时间---改了一版，没啥用了
        'dtend' => ['type' => 'int', 'null' => false, 'default' => 0],//结束时间
        'dtlength' => ['type' => 'int', 'null' => false, 'default' => 0],//检测时长
        'samples' => ['type' => 'int', 'null' => false, 'default' => 0],//样品数
        'success_samples' => ['type' => 'int', 'null' => false, 'default' => 0],//样品数
        'result' => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],//检测结果
        'connect_type' => ['type' => 'varchar(255)', 'null' => false, 'default' => ''],
        'ctime' => ['type' => 'int', 'null' => false, 'default' => 0],//创建时间 - 即审批通过时间
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
        'project' => ['fields' => ['project']],
        'service' => ['fields' => ['service']],
        'apply' => ['fields' => ['apply']],
        'user' => ['fields' => ['user']],
        'operator' => ['fields' => ['operator']],
        'ref_no' => ['fields' => ['ref_no']],
        'status' => ['fields' => ['status']],
        'dtstart' => ['fields' => ['dtstart']],
        'dtend' => ['fields' => ['dtend']],
    ],
];
