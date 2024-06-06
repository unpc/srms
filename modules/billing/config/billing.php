<?php

$config['export_columns.transactions'] = [
    'ref_no'=> [
        'title'=> '编号',
        'weight'=> 1,
    ],
    'date'=> [
        'title'=> '日期',
        'weight'=> 2,
    ],
    'department'=> [
        'title'=> '财务部门',
        'weight'=> 3,
    ],
    'lab' => [
        'title' => '实验室',
        'weight' => 4,
    ],
    'income'=> [
        'title'=> '收入',
        'weight'=> 5,
    ],
    'outcome'=> [
        'title'=> '支出',
        'weight'=> 6,
    ],
    'description'=> [
        'title'=> '备注',
        'weight'=> 8,
    ],
    'certificate'=> [
        'title'=> '凭证号',
        'weight'=> 9,
    ],
];
	
$config['export_columns.billings'] = [
	'billing_department' => '财务部门',
	'lab' => '实验室',
    'income_remote'=> '远程充值',
    'income_remote_confirmed'=> '有效远程充值',
	'income_local' => '本地充值',
    'income_transfer' => '转入调账',
	'outcome_remote' => '远程扣费',
    'outcome_local'=> '本地扣费',
    'outcome_use'=> '使用扣费',
    'outcome_transfer'=> '转出调账',
	'balance' => '可用余额',
	'credit_line' => '信用额度',
];

$config['default_certificate_length'] = 32;

$config['transactions.sortable_columns'] = [
    'date',
    'lab_id',
    'income',
    'outcome',
];

$config['lab.transactions.sortable_columns'] = [
    'department',
    'date',
    'lab_id',
    'income',
    'outcome',
    'certificate',
];