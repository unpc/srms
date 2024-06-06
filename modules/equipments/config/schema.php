<?php

$config['eq_record'] = [
	'fields' => [
		# 对应仪器
		'equipment' => ['type'=>'object', 'oname'=>'equipment'],
		# 对应用户
		'user' => ['type'=>'object', 'oname'=>'user'],
		# 代开管理员
		'agent' => ['type'=>'object', 'oname'=>'user'],
		# 使用时间
		'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		# 
		'status' => ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
		'feedback' => ['type'=>'varchar(500)', 'null'=>TRUE],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'samples' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'is_locked'=>['type'=>'int(1)', 'null'=>FALSE, 'default'=>0],
		'use_type' => ['type'=>'tinyint', 'null'=>FALSE, 'default'=>1],
		'use_type_desc' => ['type'=>'varchar(500)', 'null'=>TRUE],
		//【中南大学】-RQ181907机主编辑使用记录时有一个可以填写的备注框
		'charge_desc' => ['type'=>'text', 'null'=>TRUE],
		'duty_teacher' => ['type'=>'object', 'oname'=>'user'],

        'preheat' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 预热时间
        'cooling' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 冷却时间

		'is_segment' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 是否切割记录
	],
	'indexes' => [
		'equipment' => ['fields'=>['equipment']],
		'user' => ['fields'=>['user']],
		'agent' => ['fields'=>['agent']],
		'dtstart' => ['fields'=>['dtstart']],
		'dtend' => ['fields'=>['dtend']],
		'status' => ['fields'=>['status']],
		'mtime'=>['fields'=>['mtime']],
        'preheat' => ['fields' => ['preheat']],
        'cooling' => ['fields' => ['cooling']],
		'duty_teacher' => ['fields'=>['duty_teacher']],
		'is_segment' => ['fields' => ['is_segment']],
	],
];

$config['eq_status'] = [
	'fields' => [
		'equipment' => ['type'=>'object', 'oname'=>'equipment'],
		'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'status' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'description' => ['type'=>'text', 'null'=>TRUE],
	],
	'indexes' => [
		'dtstart' => ['fields'=>['dtstart']],
		'dtend' => ['fields'=>['dtend']],
		'equipment' => ['fields'=>['equipment']],
		'status' => ['fields'=>['status']],
		'ctime' => ['fields'=>['ctime']],
	],
];

$config['equipment']['fields'] = (array)$config['equipment']['fields'] + [
	#仪器名称 name
	'name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
	#英文名称 en_name
	'en_name'=>['type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''],
	#所属单位 organization
	'organization'=>['type'=>'varchar(250)', 'null'=>FALSE, 'default'=>''],
	#仪器编号 reference number
	'ref_no'=>['type'=>'varchar(150)', 'null'=>TRUE, 'default'=>NULL],
	#分类号 catalog number
	'cat_no'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
	#仪器名称拼音 name abbrev
	'name_abbr' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
	#型号 model number
	'model_no'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
	#规格 Specifications_number
	'specification'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
	#金额 price
	'price'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
	#制造国家 manufacturer at
	'manu_at'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
	#生产厂家 manufacturer
	'manufacturer'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
	#出厂日期 manufactured date
	'manu_date'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	#购置日期 purchased date
	'purchased_date'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	#存放地点 location
	// 'location'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
	'location'=>['type' => 'object', 'oname' =>'tag_location'],
	#房间号 location2
	'location2'=>['type'=>'varchar(150)','null'=>FALSE, 'default'=>''],
	#控制方式 control_mode
	#control_mode 有四种情况： power/computer/nocontrol/bluetooth(通用可配)
	'control_mode'=>['type'=>'varchar(20)', 'null'=>FALSE, 'default'=>''],
	#受控地址 control_address 某种程度上就是uuid
	'control_address'=>['type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''],
	#对应的 server 地址
	'server' => ['type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''],
	'is_using'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
	'user_using' => ['type' => 'object', 'oname' => 'user'],
	#使用培训 require_training
	'require_training'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
	#锁定机主的控制方式
	'lock_incharge_control'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
	'status'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	'connect' => ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
	'is_monitoring'=>['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
	'is_monitoring_mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
    'atime'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0],//access time 入网时间
	'access_code'=>['type'=>'varchar(32)', 'null'=>TRUE],
	// 仪器的组织机构
	'group' => ['type'=>'object', 'oname'=>'tag_group'],
	'tag_root' => ['type'=>'object', 'oname'=>'tag'],
	'phone'=>['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''],
	'email'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
	'share' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0],
    // 是否隐藏
    'hidden' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0],
    'is_top' => ['type' => 'tinyint', 'null' => FALSE, 'default' => 0], // 是否置顶
    'top_time' => ['type' => 'int', 'null' => FALSE, 'default' => 0], // 置顶时间
];

$config['equipment']['indexes'] = (array)$config['equipment']['indexes'] + [
	'name'=>['fields'=>['name']],
	'en_name'=>['fields'=>['en_name']],
	'organization'=>['fields'=>['organization']],
	'ref_no'=>['type'=>'unique','fields'=>['ref_no']],
	'cat_no'=>['fields'=>['cat_no']],
	'model_no'=>['fields'=>['model_no']],
	'manu_at'=>['fields'=>['manu_at']],
	'manufacturer'=>['fields'=>['manufacturer']],
	'manu_date'=>['fields'=>['manu_date']],
	'purchased_date'=>['fields'=>['purchased_date']],
	'location'=>['fields'=>['location']],
	'control_mode'=>['fields'=>['control_mode']],
	'control_address'=>['fields'=>['control_address']],
	'server'=>['fields'=>['server']],
	'require_training'=>['fields'=>['require_training']],
	'lock_incharge_control'=>['fields'=>['lock_incharge_control']],
	'ctime'=>['fields'=>['ctime']],
	'mtime'=>['fields'=>['mtime']],
	'atime'=> ['fields'=> ['atime']],
	'user_using' => ['fields' => ['user_using']],
	'connect' => ['fields' => ['connect']],
	'is_monitoring_mtime'=>['fields'=>['is_monitoring_mtime']],
	'access_code'=>['type'=>'unique', 'fields'=>['access_code']],		
	'group'=>['fields'=>['group']],
    'phone'=>['fields'=>['phone']],
    'email'=>['fields'=>['email']],
    'share' => ['fields' => ['share']]
];

$config['ue_training'] = [
	'fields' => [
		'user'=>['type'=>'object', 'oname'=>'user'],
		'proposer' => ['type' => 'object', 'oname' => 'user'],
		'equipment'=>['type'=>'object', 'oname'=>'equipment'],
		'status' => ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		//被培训用户的类型（朱洪杰）
		'type' => ['type'=>'int', 'null'=>TRUE, 'default'=>0],
		/* TASK#319(xiaopei.li@2011.03.08) 仪器培训增加期限 */
		'atime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'description' => ['type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''],
		// 签到时间
		'check_time' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'user_equipment'=>['fields'=>['user','equipment']],
		'mtime'=>['fields'=>['mtime']],
		'ctime'=>['fields'=>['ctime']],
		'atime'=>['fields'=>['atime']],
		'check_time'=>['fields'=>['check_time']],
		'status'=>['fields'=>['status']],
		'type'=>['fields'=>['type']],
	],
];

$config['ge_training'] = [
	'fields' => [
		'user'=>['type'=>'object', 'oname'=>'user'],
		'ntotal'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'napproved' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'date' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'equipment'=>['type'=>'object', 'oname'=>'equipment'],
	],
	'indexes' => [
		'date'=>['fields'=>['date']],
	],	
];

$config['eq_announce'] = [
	'fields' => [
		'title' => ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''],
		'content' => ['type' => 'text'],
		'author' => ['type' => 'object', 'oname' => 'user'],
		'equipment' => ['type' => 'object', 'oname' => 'equipment'],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'is_sticky'=>['type'=>'tinyint(1)', 'null'=>FALSE, 'default'=>0],
        'dtstart' => ['type'=>'int', 'null'=>TRUE, 'default'=>0],
        'dtend' => ['type'=>'int', 'null'=>TRUE, 'default'=>0]
		],
	'indexes' => [
		'equipment' => ['fields' => ['equipment']],
		'author' => ['fields' => ['author']],
		'title' => ['fields' => ['title']],
		],
];

// eq_meter 相关表的定义
$config['eq_meter'] = [
	'fields' => [
		#eq_meter名称 name
		'name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		#关联的仪器
		'equipment'=>['type'=>'object', 'oname'=>'equipment'],
		'uuid'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
	],
	'indexes' => [
		'name'=>['fields'=>['name']],
		'equipment'=> ['fields'=> ['equipment']],
		'uuid'=>['fields'=>['uuid'], 'type'=>'unique'],
	],
];

$config['eq_meter_data'] = [
	'fields'=> [
		'eq_meter'=> ['type'=> 'object', 'oname'=> 'eq_meter'],
		'ctime'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0], //存储时间
		'amp'=> ['type'=> 'double', 'null'=> FALSE, 'default'=> 0],
		'voltage'=> ['type'=> 'double', 'null'=> FALSE, 'default'=> 0],
		'watt'=> ['type'=> 'double', 'null'=> FALSE, 'default'=> 0],
	],
	'indexes'=> [
		'eq_meter'=> ['fields'=> ['eq_meter']],
		'ctime'=> ['fields'=> ['ctime']],
		'amp'=> ['fields'=> ['amp']],
		'voltage'=> ['fields'=> ['voltage']],
		'watt'=> ['fields'=> ['watt']],
		
	]
];

//根据current获取的record记录, 生成规律根据设定的阈值来作类似于仪器记录的处理操作
$config['eq_meter_record'] = [
	'fields'=> [
		'eq_meter'=> ['type'=> 'object', 'oname'=> 'eq_meter'],
		'dtstart'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0],
		'dtend'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0],
		'ctime'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0],
	],
	'indexes'=> [
		'eq_meter'=> ['fields'=> ['eq_meter']],
		'dtstart'=> ['fields'=> ['dtstart']],
		'dtend'=> ['fields'=> ['dtend']],
		'ctime'=> ['fields'=> ['ctime']],
	]
];

$config['eq_preheat_cooling'] = [
    'fields' => [
        'equipment' => ['type'=>'object', 'oname'=>'equipment'],
        'preheat_time' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'cooling_time' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'status' => ['type'=>'int', 'null'=>FALSE, 'default'=>1],
    ],
    'indexes' => [
        'equipment' => ['fields'=>['equipment']],
        'status' => ['fields'=>['status']],
        'ctime' => ['fields'=>['ctime']],
    ],
];

$config['eq_record']['fields']['eq_abbr'] = ['type' => 'varchar(150)', 'null' => TRUE, 'default' => ''];
$config['eq_record']['fields']['user_abbr'] = ['type' => 'varchar(150)', 'null' => TRUE, 'default' => ''];
$config['eq_record']['fields']['agent_abbr'] = ['type' => 'varchar(150)', 'null' => TRUE, 'default' => ''];

$config['eq_record']['indexes']['eq_abbr'] = ['fields'=>['eq_abbr']];
$config['eq_record']['indexes']['user_abbr'] = ['fields'=>['user_abbr']];
$config['eq_record']['indexes']['agent_abbr'] = ['fields'=>['agent_abbr']];

$config['equipment']['fields']['using_abbr'] = ['type' => 'varchar(150)', 'null' => TRUE, 'default' => ''];
$config['equipment']['fields']['contacts_abbr'] = ['type' => 'varchar(250)', 'null' => TRUE, 'default' => ''];
$config['equipment']['fields']['location_abbr'] = ['type' => 'varchar(250)', 'null' => TRUE, 'default' => ''];

$config['equipment']['indexes']['using_abbr'] = ['fields'=>['using_abbr']];
$config['equipment']['indexes']['contacts_abbr'] = ['fields'=>['contacts_abbr']];
$config['equipment']['indexes']['location_abbr'] = ['fields'=>['location_abbr']];

//假期仪器对应非时段预约记录
$config['holiday_reserv'] = [
    'fields' => [
        # 对应仪器
        'equipment' => ['type'=>'object', 'oname'=>'equipment'],
        'source' => ['type'=>'object'],//对应的非时段预约
        'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
        'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes' => [
        'equipment' => ['fields'=>['equipment']],
        'source' => ['fields'=>['source']],
    ],
];

$config['equipment']['fields']['require_dteacher'] = ['type' => 'tinyint', 'null' => FALSE, 'default' => 0];
$config['equipment']['indexes']['require_dteacher'] = ['fields' => 'require_dteacher'];

$config['usenotice_record'] = [
    'fields' => [
        'receiver' => ['type' => 'object', 'oname' => 'user'], // 收件人
        'equipment' => ['type' => 'object', 'oname' => 'equipment'], // 仪器
        'record' => ['type' => 'object', 'oname' => 'eq_record'], // 触发收件的使用记录
        'notif_key' => ['type' => 'varchar(64)', 'null' => false, 'default' => ''], // 发件key
        'short_key' => ['type' => 'varchar(32)', 'null' => false, 'default' => ''], // 短key
        'ctime' => ['type' => 'int', 'null' => false, 'default' => 0], // 寄件时间
        'type' => ['type' => 'varchar(32)', 'null' => false, 'default' => ''], // 消息发送类型
        'is_read' => ['type' => 'tinyint(1)', 'null' => false, 'default' => 0],
    ],
    'indexes' => [
        'receiver' => ['fields' => ['receiver']],
        'equipment' => ['fields' => ['equipment']],
        'record' => ['fields' => ['record']],
        'ctime' => ['fields' => ['ctime']],
    ],
];

$config['eq_record']['fields']['billing_fund'] = ['type' => 'object', 'oname' => 'billing_fund'];
$config['eq_record']['indexes']['billing_fund'] = ['fields' => 'billing_fund'];
