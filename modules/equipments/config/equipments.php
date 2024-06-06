<?php
$config['export_columns.eq_record'] = [
	'-1' => '仪器信息',
	'equipment' => '仪器名称',
	'eq_ref_no' => '仪器编号',
	'eq_cf_id' => '仪器CF_ID',
	'eq_group' => '仪器组织机构',
	// 'eq_cat' => '仪器分类',
	// 'eq_incharge' => '仪器负责人',
	'-2' => '使用者信息',
	'user' => '使用者',
	// 'member_type' => '用户类型',
	'lab'=> '实验室',
	'user_group' => '用户组织机构',
	'-3'=>'使用信息',
	'record_ref_no'=>'记录编号',
	'date' => '时段',
	'total_time' => '总时长',
	'total_time_hour' => '总时长(/h)',
	'samples' => '样品数',
	'charge_amount' => '收费金额',
	'agent' => '代开',
	'status' => '反馈',
	'description' => '备注', 
];

$config['export_columns.equipment'] = [
	'-1' => '仪器信息',
	'name' => '仪器名称',
	'ref_no' => '仪器编号',
    'price' => '仪器价格',
	'eq_cf_id' => '仪器CF_ID',
	'cat' => '仪器分类',
	'control_mode' => '控制方式',
	'location' => '存放地点',
	'contacts' => '联系人',
	'phone' => '联系方式',	
	'group' => '组织机构',
    'atime' => '入网日期',
	
	'-2' => '仪器参数信息',
	'specification' => '规格',
	'model_no' => '型号',
	'manufacturer' => '生产厂家',
	'manu_at' => '制造国家',
	'purchased_date' => '购置日期',
	'manu_date' => '出厂日期',
	'cat_no' => '分类号',
	'tech_specs' => '主要规格及技术指标',
	'features' => '主要功能及特色',
	'configs' => '主要附件及配置',
	// 'incharges' => '仪器负责人',
];

$config['export_columns.training.approved'] = [
    'user'=> '姓名',
    'lab'=> '实验室',
    'group'=> '组织机构',
    'phone'=> '联系电话',
    'email'=> '电子邮箱',
    // 'address'=> '地址',
    'equipment'=> '仪器名称',
    'ctime'=> '通过时间',
    'atime'=> '过期时间',
    'description' => '授权备注'
];

$config['export_columns.training.overdue'] = [
    'user'=> '姓名',
    'lab'=> '实验室',
    'group'=> '组织机构',
    'phone'=> '联系电话',
    'email'=> '电子邮箱',
    // 'address'=> '地址',
    'atime'=> '过期时间',
];

$config['export_columns.training.applied'] = [
    'user'=> '姓名',
    'lab'=> '实验室',
    'group'=> '组织机构',
    'phone'=> '联系电话',
    'email'=> '电子邮箱',
    'ctime'=> '申请时间',
    'check_time' => '签到时间',
    // 'address'=> '地址',
];

$config['export_columns.training.group'] = [
    'user'=> '负责人',
    'ntotal'=> '总培训人数',
    'napproved'=> '通过人数',
    'date'=> '培训时间',
    'description'=> '描述',
];

$config['import_columns.equipment'] = [
    'A' => 'name',
	'B' => 'model_no',
	'C' => 'ref_no',
	'D' => 'specification',
	'E' => 'group_id',
	'F' => 'cat_no',
	'G' => 'features'
];

$config['import_columns.judge.equipment'] = [
    'A' => '仪器名称',
	'B' => '型号',
	'C' => '仪器编号',
	'D' => '规格',
	'E' => '所属单位',
	'F' => '分类号',
	'G' => '主要功能及特色'
];

$config['eq_record.sortable_columns'] = [
    'user_name',
    'equipment_name',
    'samples',
    'agent',
];

$config['equipment.sortable_columns'] = [
    'atime',
    'contact',
    'location',
    'current_user',
    'control',
    'price',
    'name',
    'current_user'
];

$config['training.sortable_columns'] = [
    'atime',
    'mtime',
    'ctime',
    'address',		//用户地址
    'user',
    // 'contact_info',	//用户联系方式
    'equipment',
    'location',		//仪器放置地址
    'control',
    'contact'		//仪器联系人
];

$config['ge_training.sortable_columns'] = [
    'user',
    'ntotal',
    'address',
    'napproved',
    'date'
];

$config['export_columns.dutys'] = [
    'duty_teacher_id' => '值班老师',
    'used_dur' => '使用机时(H)',
    'sample_dur' => '送样机时(H)',
    'record_counts' => '使用样品数(个)',
    'sample_counts' => '送样样品数(个)',
    'amount' => '收费金额(元)',
    'service_users' => '服务用户数(个)',
    'service_labs' => '服务课题组数(个)',
];

//仪器列表默认展示字段(表头)
$config['list_default_show_columns'] = [
    'ref_no' => [
        'title' => '仪器编号',
        'show' => true
    ],
    'name' => [
        'title' => '仪器名称',
        'show' => true
    ],
    'en_name' => [
        'title' => '英文名称',
        'show' => false
    ],
    'model_no' => [
        'title' => '型号',
        'show' => false
    ],
    'specification' => [
        'title' => '规格',
        'show' => false
    ],
    'price' => [
        'title' => '价格',
        'show' => false
    ],
    'manu_at' => [
        'title' => '制造国家',
        'show' => false
    ],
    'manufacturer' => [
        'title' => '生产厂家',
        'show' => false
    ],
    'manu_date' => [
        'title' => '出厂日期',
        'show' => false
    ],
    'purchased_date' => [
        'title' => '购置日期',
        'show' => false
    ],
    'atime' => [
        'title' => '入网日期',
        'show' => false
    ],
    'cat_no' => [
        'title' => '分类号',
        'show' => false
    ],
    'group' => [
        'title' => '组织机构',
        'show' => false
    ],
    'location' => [
        'title' => '放置房间',
        'show' => true
    ],
    'current_user' => [
        'title' => '当前使用者',
        'show' => true
    ],
    'control' => [
        'title' => '控制方式',
        'show' => true
    ],
    'contact' => [
        'title' => '联系人',
        'show' => true
    ],
    'phone' => [
        'title' => '联系电话',
        'show' => false
    ],
    'email' => [
        'title' => '联系邮箱',
        'show' => false
    ],
];