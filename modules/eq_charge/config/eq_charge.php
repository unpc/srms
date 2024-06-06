<?php

$config['equipment.msg.model'] = [
	'description'=>'设置仪器收费信息更新提示',
	'body'=>'%subject 于 %date 修改了 %equipment 的收费信息',
	'strtr'=>[
		'%subject'=>'预约者',
		'%equipment'=>'仪器名称',
		'%date'=>'时间'
	],
];

$config['export_columns.eq_charge'] = [
	'-1' => '仪器信息',
	'equipment' => '仪器名称',
	'eq_ref_no' => '仪器编号',
	'eq_cf_id' => '仪器CF_ID',
	'eq_group' => '仪器组织机构',
	'incharge' => '联系人',
	'-2' => '用户信息',
	'user' => '用户名称',
	'lab' => '实验室',
	'user_group' => '用户组织机构',
	'-3' => '收费信息',
	'charge_ref_no' => '计费编号',
	'date' => '时间',
	'samples' => '样品数',
	'amount' => '收费金额',
    'type' => '收费类型',
    'charge_time' => '计费时段',
 	'description' => '备注',
    'duty_teacher' => '值班老师',
];

$config['template']['time_reserv_record'] = [
    'title'=> '综合预约 / 使用时间智能计费',
    'content'=> [
        'reserv'=> [
            'script' => 'private:reserv/reserv_time.lua',
            'params' => [
                '%options'=>['*' => ['minimum_fee' => 0,'unit_price' => 0]]
            ]
        ],
        'record'=> [
            'script'=> 'private:reserv/record_time.lua',
            'params' => [
                '%options'=>['*' => ['minimum_fee' => 0,'unit_price' => 0]]
            ]
        ]
    ],
    'i18n_module' => 'eq_charge',
    'category'=>'reserv',
];

$config['template']['only_reserv_time'] = [
    'title'=> '按预约时间',
    'content'=> [
        'reserv'=> [
            'script'=> 'private:reserv/only_reserv_time.lua',
            'params' => [
                '%options'=>['*' => ['minimum_fee' => 0,'unit_price' => 0]]
            ]
        ]
    ],
    'i18n_module' => 'eq_charge',
    'category'=> 'reserv'
];

$config['template']['record_time'] = [
    'title'=> '按使用时间',
    'content'=> [
        'record'=> [
            'script'=> 'private:record/time.lua',
            'params' => [
                '%options'=>['*' => ['minimum_fee' => 0,'unit_price' => 0]]
            ]
        ]
    ],
    'i18n_module' => 'eq_charge',
    'category'=> 'record'
];

$config['template']['record_times'] = [
    'title'=> '按使用次数',
    'content'=> [
        'record'=> [
            'script'=> 'private:record/times.lua',
            'params' => [
                '%options'=>['*' => ['minimum_fee' => 0,'unit_price' => 0]]
            ]
        ]
    ],
    'i18n_module' => 'eq_charge',
    'category'=> 'record'
];

$config['template']['record_samples'] = [
    'title'=> '按样品数',
    'content'=> [
        'record'=> [
            'script' => 'private:record/samples.lua',
            'params' => [
                '%options'=>['*' => ['minimum_fee' => 0,'unit_price' => 0]]
            ]
        ]
    ],
    'i18n_module' => 'eq_charge',
    'category'=> 'record'
];

$config['template']['sample_count'] = [
    'title'=> '按样品数',
    'content'=> [
        'sample'=> [
            'script'=> 'private:sample/count.lua',
            'params' => [
                '%options'=>['*' => ['minimum_fee' => 0,'unit_price' => 0]]
            ]
        ]
    ],
    'i18n_module' => 'eq_charge',
    'category'=> 'sample'
];

$config['template']['sample_time'] = [
    'title'=> '按测样时间',
    'content'=> [
        'sample'=> [
            'script'=> 'private:sample/sample_time.lua',
            'params' => [
                '%options'=>['*' => ['minimum_fee' => 0,'unit_price' => 0]]
            ]
        ]
    ],
    'i18n_module' => 'eq_charge',
    'category'=> 'sample'
];

//推荐的计费方式
$config['recommend_template'] = [
    'time_reserv_record'
];

$config['print_max'] =  500;

$config['sortable_columns'] = [
    'user',
    'type',
    'equipment',
    'amount',
    'status'
];
// 触发计费的key
$config['calculate_keys']['record'] = [
    'dtend', 'user', 'reserv', 'samples', 'charge_tags',
    'extra_fields', 'project', 'cooling', 'preheat'
];

$config['template']['record_time_discount'] = [
    'title'=> '按使用时间折扣计费',
    'content'=> [
        'record'=> [
            'script'=> 'private:record/time_discount.lua',
            'params' => [
                '%options'=>['*' => ['minimum_fee' => 0,'unit_price' => 0/*这里代表着正常收费，每小时收费多少*/]]
            ]
        ]
    ],
    'i18n_module' => 'eq_charge',
    'category'=> 'record'
];
