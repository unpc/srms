<?php

$config['analysis'] = [
    'fields' => [
        'user' => ['type' => 'object', 'oname' => 'user', 'comment' => '使用者'],
        'lab' => ['type' => 'object', 'oname' => 'lab', 'comment' => '课题组'],
        'equipment' => ['type' => 'object', 'oname' => 'equipment', 'comment' => '仪器'],
        'project' => ['type' => 'int', 'null' => TRUE, 'comment' => '项目'],
        'use_type' => ['type' => 'varchar(11)','null' => false,'default' => '','comment' => '使用类型'],
        'use_dur' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '使用时长'],
        'sample_dur' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '送样时长'],
        'reserv_dur' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '预约时长'],
        'use_time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '使用次数'],
        'sample_time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '送样次数'],
        'reserv_time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '预约次数'],
        'use_fee' => ['type' => 'double', 'null' => FALSE, 'default' => 0, 'comment' => '使用计费'],
        'sample_fee' => ['type' => 'double', 'null' => FALSE, 'default' => 0, 'comment' => '送样计费'],
        'reserv_fee' => ['type' => 'double', 'null' => FALSE, 'default' => 0, 'comment' => '预约计费'],
        'success_sample' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '样品数量总数'],
        'use_sample' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '使用测样数'],
        'sample_sample' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '送样测样数'],
        'use_project' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '使用项目数'],
        'sample_project' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '送样项目数'],
        'reserv_project' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '预约项目数'],
        'time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '时间'],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
    ],
    'indexes' => [
        'user' => ['fields' => ['user']],
        'lab' => ['fields' => ['lab']],
        'equipment' => ['fields' => ['equipment']],
        'project' => ['fields' => ['project']],
        'time' => ['fields' => ['time']],
        'ctime' => ['fields' => ['ctime']],
        'mtime' => ['fields' => ['mtime']],
    ]
];

$config['analysis_training'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment', 'comment' => '仪器'],
        'student_count' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '培训学生数'],
        'teacher_count' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '培训教师数'],
        'other_count' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '培训其他人数'],
        'apply_count' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '申请总人数'],
        'time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '时间'],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
        'time' => ['fields' => ['time']],
        'ctime' => ['fields' => ['ctime']],
        'mtime' => ['fields' => ['mtime']],
    ]
];

$config['analysis_maintain'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment', 'comment' => '仪器'],
        'dtstart' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '维修开始时间'],
        'dtend' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '维修结束时间'],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
        'ctime' => ['fields' => ['ctime']],
        'mtime' => ['fields' => ['mtime']],
    ]
];

$config['analysis_mark'] = [
    'fields' => [
        'use_type' => ['type' => 'varchar(11)', 'null' => FALSE, 'default' => ''],
        'source_name' => ['type' => 'varchar(11)', 'null' => FALSE, 'default' => ''],
        'source_id' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'user' => ['type' => 'object', 'oname' => 'user'],
        'lab' => ['type' => 'object', 'oname' => 'lab'],
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'project' => ['type' => 'int', 'null' => FALSE, 'default' => -1],
        'date' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 数据牵连的时间
        'time' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 数据修改的时间
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
    ],
    'indexes' => [
        'use_type' => ['fields' => ['use_type']],
        'user' => ['fields' => ['user']],
        'lab' => ['fields' => ['lab']],
        'equipment' => ['fields' => ['equipment']],
        'project' => ['fields' => ['project']],
        'time' => ['fields' => ['time']],
        'ctime' => ['fields' => ['ctime']],
        'mtime' => ['fields' => ['mtime']],
    ]
];

$config['analysis_mark_training'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment'],
        'date' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 数据牵连的时间
        'time' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 数据修改的时间
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
        'time' => ['fields' => ['time']],
        'ctime' => ['fields' => ['ctime']],
        'mtime' => ['fields' => ['mtime']],
    ]
];

$config['analysis_achievement'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment', 'comment' => '仪器'],
        'achievement' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '成果'],
        'type' => ['type' => 'varchar(50)', 'null' => FALSE, 'default' => '', 'comment' => '成果类型'],
        'date' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '日期'],
    ],
    'indexes' => [
        'equipment' => ['fields' => ['equipment']],
        'achievement' => ['fields' => ['achievement']],
        'date' => ['fields' => ['date']],
    ]
];

//仪器使用明细：标识，仪器名称，仪器编号，仪器组织机构，使用者，课题组，用户机构，记录编号，使用时段，时长，时间总计，样品数，代开，反馈，使用收费
$config['analysis_eq_record'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment','comment' => '仪器'],
        'equipment_name' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '仪器名称'],
        'ref_no' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '仪器编号'],
        'group' => ['type' => 'object','oname'=>'tag','null' => false,'comment' => '组织机构'],
        'group_name' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '组织机构名称'],
        'user' => ['type' => 'object', 'oname' => 'user','comment' => '使用者'],
        'user_name' => ['type' => 'varchar(250)','null' => false,'default' => '','comment' => '用户名称'],
        'lab' => ['type' => 'object', 'oname' => 'lab','comment' => '课题组'],
        'lab_name' => ['type' => 'varchar(250)','null' => false,'default' => '','comment' => '课题组名称'],
        'user_group' => ['type' => 'object','oname'=>'tag','null' => false,'comment' => '用户组织机构'],
        'user_group_name' => ['type' => 'varchar(250)','null' => false,'default' => '','comment' => '用户机构'],
        'record_id' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => '0','comment' => '记录编号'],
        'reserv_id' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '关联预约记录编号'],
        'is_locked' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '是否锁定'],
        'dtstart' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '使用开始时间'],
        'dtend' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '使用结束时间'],
        'time_total' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '使用时长'],
        'samples' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '样品数'],
        'agent' => ['type'=>'object', 'oname'=>'user','comment' => '代开'],
        'feedback' => ['type'=>'varchar(500)', 'null'=>false,'default'=>'','comment' => '反馈'],
        'amount' => ['type'=>'double', 'null'=>false,'default'=>0,'comment' => '使用收费'],
        'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '时间'],
        'record_create_time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '创建时间'],
        'project' => ['type' => 'int', 'null' => TRUE, 'comment' => '项目'],
    ],
    'indexes' => [
        'equipment' => ['fields'=>['equipment']],
        'ref_no' => ['fields'=>['ref_no']],
        'user' => ['fields'=>['user']],
        'agent' => ['fields'=>['agent']],
        'dtstart' => ['fields'=>['dtstart']],
        'dtend' => ['fields'=>['dtend']],
        'amount' => ['fields'=>['amount']],
        'project' => ['fields'=>['project']],
        'is_locked' => ['fields'=>['is_locked']],
    ]
];

//标识，仪器名称，仪器编号，仪器组织机构，申请人，课题组，申请人组织机构，送样编号，送样时间，测样开始时间，测样结束时间，取样时间，状态，样品数，测样成功数，操作者，收费，描述，备注
$config['analysis_eq_sample'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment','comment' => '仪器'],
        'equipment_name' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '仪器名称'],
        'ref_no' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '仪器编号'],
        'group' => ['type' => 'object','oname'=>'tag','null' => false,'comment' => '组织机构'],
        'group_name' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '组织机构名称'],
        'sender' => ['type' => 'object', 'oname' => 'user','comment' => '申请人'],
        'sender_name' => ['type' => 'varchar(250)','null' => false,'default' => '','comment' => '用户名称'],
        'lab' => ['type' => 'object', 'oname' => 'lab','comment' => '课题组'],
        'sender_group' => ['type' => 'varchar(250)','null' => false,'default' => '','comment' => '申请人组织机构'],
        'sample_id' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '送样编号'],
        'dtsubmit' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '送样时间'],
        'dtstart' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '测样开始时间'],
        'dtend' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '测样结束时间'],
        'dtpickup' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '取样时间'],
        'status' => ['type'=>'int', 'null'=>FALSE, 'default'=>0,'comment' => '状态'],
        'samples' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '样品数'],
        'is_locked' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '是否锁定'],
        'success_samples' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '成功样品数'],
        'operator' => ['type'=>'object', 'oname'=>'user','comment' => '操作者'],
        'record' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '关联记录ID'],
        'amount' => ['type'=>'double', 'null'=>false,'default'=>0,'comment' => '收费'],
        'description' => ['type'=>'varchar(250)', 'null'=>false,'default'=>0,'comment' => '描述'],
        'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'record_create_time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '创建时间'],
        'time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '时间'],
        'project' => ['type' => 'int', 'null' => TRUE, 'comment' => '项目'],
    ],
    'indexes' => [
        'equipment' => ['fields'=>['equipment']],
        'ref_no' => ['fields'=>['ref_no']],
        'sender' => ['fields'=>['sender']],
        'samples' => ['fields'=>['samples']],
        'success_samples' => ['fields'=>['success_samples']],
        'amount' => ['fields'=>['amount']],
        'project' => ['fields'=>['project']],
        'is_locked' => ['fields'=>['is_locked']],
    ]
];

//标识，仪器名称，仪器编号，仪器组织机构，预约者姓名，预约者联系方式，预约者组织机构，课题组，时段，时长，预约类型，备注
$config['analysis_eq_reserv'] = [
    'fields' => [
        'equipment' => ['type' => 'object', 'oname' => 'equipment','comment' => '仪器'],
        'equipment_name' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '仪器名称'],
        'ref_no' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '仪器编号'],
        'group' => ['type' => 'object','oname'=>'tag','null' => false,'comment' => '组织机构'],
        'group_name' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '组织机构名称'],
        'user' => ['type' => 'object', 'oname' => 'user','comment' => '预约者'],
        'user_name' => ['type' => 'varchar(50)','comment' => '预约者姓名'],
        'user_phone' => ['type' => 'varchar(11)','null' => false,'comment' => '预约者联系方式'],
        'user_group' => ['type' => 'varchar(50)','null' => false,'default' => '','comment' => '预约者组织机构'],
        'lab' => ['type' => 'object', 'oname' => 'lab','comment' => '课题组'],
        'dtstart' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '预约开始时间'],
        'dtend' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '预约结束时间'],
        'dtlength' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '时长'],
        'reserv_id' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '预约编号'],
        'type' => ['type' => 'int', 'null' => FALSE, 'default' => 0,'comment' => '预约类型'],
        'description' => ['type' => 'varchar(500)', 'null' => FALSE, 'default' => '','comment' => '备注'],
        'mtime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '时间'],
        'record_create_time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '创建时间'],
        'project' => ['type' => 'int', 'null' => TRUE, 'comment' => '项目'],
    ],
    'indexes' => [
        'equipment' => ['fields'=>['equipment']],
        'ref_no' => ['fields'=>['ref_no']],
        'user' => ['fields'=>['user']],
        'lab' => ['fields'=>['lab']],
        'type' => ['fields'=>['type']],
        'project' => ['fields'=>['project']],
    ]
];



//标识，标题，作者，期刊，日期，卷，刊号，标签，课题组，关联项目，关联仪器
$config['analysis_project_publication'] = [
    'fields' => [
        'title' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '标题'],
        'author' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => '', 'comment' => '作者'],
        'journal' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '期刊'],
        'date' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '日期'],
        'volume' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '卷'],
        'issue' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '刊号'],
        'lab_id' => ['type' => 'int', 'null' => FALSE, 'comment' => '课题组'],
        'project' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => '' ,'comment' => '关联项目'],
        'tag_name' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => '' ,'comment' => '标签'],
        'tag_id' => ['type' => 'int', 'null' => FALSE, 'default' => 0 ,'comment' => '标签'],
        'equipment_id' => ['type' => 'int', 'comment' => '关联仪器'],
    ],
];

//标识，名称，获奖级别，获奖日期，人员，课题组，关联项目，关联仪器
$config['analysis_project_awards'] = [
    'fields' => [
        'title' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '标题'],
        'tag_name' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => '' ,'comment' => '获奖级别'],
        'tag_id' => ['type' => 'int', 'null' => FALSE, 'default' => 0 ,'comment' => '标签'],
        'date' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '日期'],
        'author' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => '', 'comment' => '作者'],
        'lab_id' => ['type' => 'int', 'null' => FALSE, 'comment' => '课题组'],
        'project' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => '' ,'comment' => '关联项目'],
        'equipment_id' => ['type' => 'int', 'comment' => '关联仪器'],
    ],
];

//标识，名称，专利号，日期，专利类型，人员表，课题组，关联项目，关联仪器
$config['analysis_project_patent'] = [
    'fields' => [
        'title' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '标题'],
        'ref_no' => ['type' => 'varchar(150)','null' => false,'default' => '','comment' => '专利号'],
        'date' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '日期'],
        'tag_name' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => '' ,'comment' => '获奖级别'],
        'tag_id' => ['type' => 'int', 'null' => FALSE, 'default' => 0 ,'comment' => '标签'],
        'author' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => '', 'comment' => '作者'],
        'lab_id' => ['type' => 'int', 'null' => FALSE, 'comment' => '课题组'],
        'project' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => '' ,'comment' => '关联项目'],
        'equipment_id' => ['type' => 'int', 'comment' => '关联仪器'],
    ],
];

//使用记录，送样记录，预约记录原数据动态修改表
$config['analysis_mark_desc'] = [
    'fields' => [
        'source_name' => ['type' => 'varchar(32)','null' => false,'default' => '','comment' => '来源'],
        'source_id' => ['type' => 'int','null' => false,'default' => 0,'comment' => '来源ID'],
        'ctime' => ['type' => 'int','null' => false,'default' => 0,'comment' => '创建时间'],
    ],
    'indexes' => [
        'source_name' => ['fields'=>['source_name']],
        'source_id' => ['fields'=>['source_id']],
    ]
];

//eq_charge
$config['analysis_eq_charge']['fields']['user'] = ['type'=>'object', 'oname'=>'user','comment' => '用户'];
$config['analysis_eq_charge']['fields']['lab'] = ['type'=>'object', 'oname'=>'lab','comment' => '所属课题组'];
$config['analysis_eq_charge']['fields']['equipment'] = ['type'=>'object', 'oname'=>'equipment','comment' => '仪器'];
$config['analysis_eq_charge']['fields']['status'] = ['type' => 'int', 'null' => TRUE, 'default' => 0,'comment' => '状态'];
$config['analysis_eq_charge']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0,'comment' => '创建时间'];
$config['analysis_eq_charge']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0,'comment' => '更新时间'];
$config['analysis_eq_charge']['fields']['dtstart'] = ['type' => 'int(11)', 'null' => TRUE, 'default' =>0,'comment' => '开始时间'];
$config['analysis_eq_charge']['fields']['dtend'] = ['type' => 'int(11)', 'null' => TRUE, 'default' =>0,'comment' => '结束时间'];
$config['analysis_eq_charge']['fields']['auto_amount'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0,'comment' => '预估金额'];
$config['analysis_eq_charge']['fields']['amount'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0,'comment' => '实际金额'];
$config['analysis_eq_charge']['fields']['custom'] = ['type' => 'tinyint', 'null' => FALSE, 'default' => 0,'comment' => '是否自定义计费'];
$config['analysis_eq_charge']['fields']['transaction_id'] = ['type'=>'object', 'oname'=>'billing_transaction','comment' => '明细ID'];
$config['analysis_eq_charge']['fields']['transaction'] = ['type'=>'varchar(150)', 'comment' => '明细编号'];
$config['analysis_eq_charge']['fields']['is_locked'] = ['type'=>'int(1)', 'null'=>FALSE, 'default'=>0,'comment' => '锁定状态'];
$config['analysis_eq_charge']['fields']['source_id'] = ['type' => 'int(11)','null'=>FALSE,'comment' => '源ID'];
$config['analysis_eq_charge']['fields']['source_name'] = ['type' => 'varchar(150)','null'=>FALSE,'comment' => '源类型'];
$config['analysis_eq_charge']['fields']['charge_duration_blocks'] = ['type' => 'varchar(250)','null'=>FALSE,'comment' => '计费时段'];
$config['analysis_eq_charge']['fields']['description'] = ['type' => 'text','comment' => '备注'];
$config['analysis_eq_charge']['fields']['time'] = ['type' => 'int(11)', 'null' => TRUE, 'default' =>0,'comment' => '时间'];
$config['analysis_eq_charge']['fields']['record_create_time'] = ['type' => 'int(11)', 'null' => TRUE, 'default' =>0,'comment' => '记录创建时间'];
$config['analysis_eq_charge']['fields']['blstatus'] = ['type' => 'int(11)', 'null' => TRUE, 'default' =>0,'comment' => '报销状态,0未,1中,2已'];

$config['analysis_eq_charge']['indexes']['lab'] = ['fields'=>['lab']];
$config['analysis_eq_charge']['indexes']['user'] = ['fields'=>['user']];
$config['analysis_eq_charge']['indexes']['equipment'] = ['fields'=>['equipment']];
$config['analysis_eq_charge']['indexed']['status'] = ['fields' => ['status']];
$config['analysis_eq_charge']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['analysis_eq_charge']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['analysis_eq_charge']['indexes']['dtstart'] = ['fields'=>['dtstart']];
$config['analysis_eq_charge']['indexes']['dtend'] = ['fields'=>['dtend']];
$config['analysis_eq_charge']['indexes']['transaction'] = ['fields'=>['transaction']];
$config['analysis_eq_charge']['indexes']['is_locked'] = ['fields'=>['is_locked']];
$config['analysis_eq_charge']['indexes']['source_id'] = ['fields'=>['source_id']];
$config['analysis_eq_charge']['indexes']['source_name'] = ['fields'=>['source_name']];
$config['analysis_eq_charge']['indexes']['time'] = ['fields'=>['time']];

//lab_project
$config['analysis_lab_project'] = [
    'fields' => [
        'lab'=>['type'=>'object', 'oname'=>'lab'],
        'name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
        'type'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'dtstart'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'dtend'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'status'=>['type'=>'int', 'null'=>FALSE, 'default'=>0]
    ],
    'indexes'=>[
        'name'=>['type'=>'unique','fields'=>['lab', 'name', 'type']],
        'type'=>['fields'=>['type']],
        'lab'=>['fields'=>['lab']],
        'dtstart'=>['fields'=>['dtstart']],
        'dtend'=>['fields'=>['dtend']],
        'status'=>['fields'=>['status']]
    ],
];

// 用户信用分增减明细表
$config['analysis_credit_record'] = [
    'fields'  => [
        'user'        => ['type' => 'object', 'oname' => 'user', 'comment' => "使用者"], // 使用者
        'equipment'   => ['type' => 'object', 'oname' => 'equipment', 'comment' => "关联仪器"], // 关联仪器
        'credit_rule' => ['type' => 'object', 'oname' => 'credit_rule', 'comment' => "关联计分规则"], // 关联计分规则
        'ctime'       => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "计分变化时间"], // 计分变化时间
        'score'       => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "本次得分 可以正负"], // 本次得分 可以正负
        'is_auto'     => ['type' => 'int', 'null' => false, 'default' => 1, 'comment' => "是否是系统自动计分"], // 是否是系统自动计分
        'total'       => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "本次得分后当前总分"], // 本次得分后当前总分
        'description' => ['type' => 'varchar(255)', 'null' => true, 'comment' => "备注"], // 备注, 手动计分可能会用到
        'time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '推送时间'],
    ],
    'indexes' => [
        'credit_rule' => ['fields' => ['credit_rule']],
        'ctime'       => ['fields' => ['ctime']],
    ],
];
// 用户信用分等级
$config['analysis_credit_level'] = [
    'fields'  => [
        'level'      => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "等级代码"], // 等级代码
        'name'       => ['type' => 'varchar(128)', 'null' => false, 'default' => '', 'comment' => "等级名称"], // 等级名称
        'rank_start' => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "排名百分比(开始)"], // 排名百分比(开始)
        'rank_end'   => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "排名百分比(结束)"], // 排名百分比(结束)
        'ctime'      => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "生成时间"], // 生成时间
        'time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '推送时间'],
    ],
    'indexes' => [
        'level' => ['fields' => ['level']],
    ],
];

// 用户积分表
$config['analysis_credit'] = [
    'fields'  => [
        'user'         => ['type' => 'object', 'oname' => 'user', 'comment' => "用户"], // 使用者
        'credit_level' => ['type' => 'object', 'oname' => 'credit_level', 'comment' => "用户等级"], // 关联用户等级
        'ctime'        => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "生成时间"], // 生成时间
        'utime'        => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "计算用户等级时间"], // update Time 计算用户等级时间
        'mtime'        => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "记录更新时间"], // 记录更新时间
        'percent'      => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "当前超越了系统中**%的用户"], // 当前超越了系统中{percent}%的用户
        'total'        => ['type' => 'int', 'null' => false, 'default' => 0, 'comment' => "当前总分"], // 当前总分
        'time' => ['type' => 'int', 'null' => FALSE, 'default' => 0, 'comment' => '推送时间'],
    ],
    'indexes' => [
        'user' => ['fields' => ['user'], 'type' => 'unique'],
    ],
];
