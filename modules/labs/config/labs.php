<?php
$config['export_columns.labs'] = [  
	'lab_name' => '实验室名称',
	'owner' => '负责人',
	'lab_contact' => '实验室联系方式',
	'group' => '组织机构',
	'description' => '介绍',
	'creator' => '建立者',
	'auditor' => '审批者',
];

if (People::perm_in_uno()){
    unset($config['export_columns.labs']['creator']);
    unset($config['export_columns.labs']['auditor']);
    unset($config['export_columns.labs']['description']);
    unset($config['export_columns.labs']['lab_contact']);
}

$config['hide_lab'] = true;

$config['add_member_method']['new'] = '新建人员';
if ($GLOBALS['preload']['people.multi_lab']) {
	$config['add_member_method']['exsit_token'] = '从已有成员选择';
}

$config['people.sortable_columns'] = [
	'name',
	'date',
];

//开启临时PI
$config['secretary_support'] = false;

// 默认课题组表头
$config['list_default_show_columns'] = [
    'lab_name' => [
        'title' => '名称',
        'show' => true
    ],
    'group' => [
        'title' => '组织机构',
        'show' => true
    ],
    'description' => [
        'title' => '介绍',
        'show' => true
    ],
    'creator' =>  [
        'title' => '建立者',
        'show' => true
    ],
    'auditor' => [
        'title' => '审批者',
        'show' => true
    ],
    'ctime' => [
        'title' => '创建时间',
        'show' => true
    ]
];

$config['sign_edit.tips'] = '您好, 帐号注册成功, 请您联系管理员进行激活.';