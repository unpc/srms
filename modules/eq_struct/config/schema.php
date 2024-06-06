<?php

$config['eq_struct'] = [
    'fields' => [
        'token' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        // 平台编号
        'ref_no' => ['type' => 'varchar(10)', 'null' => FALSE],
        // 平台名称
        'name' => ['type' => 'varchar(150)', 'null' => FALSE],
        // 所属单位
        'group' => ['type' => 'varchar(150)', 'null' => FALSE],
        // 项目编号
        'proj_no' => ['type' => 'varchar(150)', 'null' => FALSE],
        // 财务收费账户
        'card_no' => ['type' => 'varchar(150)', 'null' => FALSE],
        // 统一支付批次号
        'pch' => ['type' => 'varchar(150)', 'null' => FALSE],
        // 统一支付平台订单号缩写
        'order_prefix' => ['type' => 'varchar(150)', 'null' => TRUE],
        // 平台类型
        'type' => ['type' => 'int', 'null' => TRUE],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'card_no' => ['type' => 'varchar(150)', 'null' => FALSE]
    ],
    'indexes' => [
        'token' => ['fields' => ['token']],
        'ref_no' => ['fields' => ['ref_no']],
        'name' => ['fields' => ['name']],
        'group' => ['fields' => ['group']],
        'proj_no' => ['fields' => ['proj_no']],
        'type' => ['fields' => ['type']],
        'ctime' => ['fields' => ['ctime']],
        'card_no' => ['fields' => ['card_no']],
    ],
];

$config['equipment']['fields']['struct'] = ['type' => 'object', 'oname' => 'eq_struct'];
$config['equipment']['indexes']['struct'] = ['fields' => ['struct']];
