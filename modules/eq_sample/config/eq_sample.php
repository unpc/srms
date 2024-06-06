<?php
$config['export_columns.eq_sample'] = [
	'-1' => '仪器信息',
	'equipment' => '仪器名称',
	'eq_ref_no' => '仪器编号',
	'eq_cf_id' => '仪器CF_ID',
	'eq_group' => '仪器组织机构',
	'-2' => '申请人信息',
	'user' => '申请人',
	'user_email' => '申请人邮箱',
	'user_phone' => '申请人电话',
	'lab'=> '实验室',
	'user_group' => '申请人组织机构',
	'-3' => '使用信息',
	'sample_ref_no' => '送样编号',
	'dtsubmit' => '送样时间',
	'dtstart' => '测样开始时间',
	'dtend' => '测样结束时间',
	'dtpickup' => '取样时间',
	'status' => '状态',
	'samples' => '样品数',
	'success_samples' => '测样成功数',
	'handlers' => '操作者',
	'amount' => '收费',
	'info' => '描述',
	'note' => '备注',
    'duty_teacher' => '值班老师',
];
//关联项目不关联
if ($GLOBALS['preload']['people.multi_lab']) {
	$config['must_connect_lab_project'] = TRUE;
}
else {
	$config['must_connect_lab_project'] = FALSE;
}

$config['approval.title'] = '精细化工和高分子材料公共技术服务平台';

$config['charge_forecast'] = true;
