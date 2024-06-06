<?php 

$config['export_columns.eq_evaluate'] = [
    '-1' => '仪器信息',
    'equipment' => '仪器名称',
    'eq_ref_no' => '仪器编号',
    'eq_cf_id' => '仪器CF_ID',
    'eq_group' => '仪器组织机构',
    '-2' => '使用者信息',
    'user' => '使用者',
    'lab'=> '实验室',
    'user_group' => '用户组织机构',
    '-3'=>'评价信息',
    'evaluate_ref_no'=>'记录编号',
    'score' => '服务态度',
    'content' => '服务评价',
    'duty_teacher' => '值班老师',
];

$config['score.require'] = true;

$config['default.rate'] = 0;

$config['rate.baseline'] = 4;

$config['rate.tip'] = ['非常差', '差', '一般', '好', '非常好'];
