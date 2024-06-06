<?php 

$config['export_columns.expenses'] = [
	'ref_no' => '编号',
	'date' => '日期',
	'portion'=>'支出类别',
	'amount' => '支出',
	'summary' => '说明',
	'invoice_no' => '发票号',
	];

$config['export_columns.summary'] = [
	'name' => '支出类别',
	'amount' => '分配份额',
	'utilization'=>'使用份额',
	'balance' => '剩余份额',
	];
	
//导出经费
$config['export_columns.grant'] = [
	'project' => '课题名称',
	'source' => '来源',
	'ref' => '编号',
	'amount' => '总额',
	'balance' => '余额',
	'incharge' => '负责人',
];

$config['remind_time'] = 0;
