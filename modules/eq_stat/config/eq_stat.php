<?php
// 在eq_stat中增加默认统计方法(xiaopei.li@2011.10.18)
$config['export_columns.eq_stat'] = [
	'-1' => '统计列表',
	'equipment' => '仪器名称',
    'eq_ref_no'=> '仪器编号',
    'eq_cf_id'=> '仪器CF_ID',
	'contact' => '联系人',
    'equipments_value' => '仪器总值',
    '-2' => '绩效信息',
    'record_sample' => '测样数',
    'time_total' => '使用机时',
    'time_open' => '开放机时',
    'time_valid' => '有效机时',
    'use_time' => '使用次数',
    'total_trainees' => '培训人数',
    'pubs' => '论文数',
    'charge_total' => '使用收费',
    ];
    
$config['export_columns.eq_perf'] = [
    'equipment' => '仪器名称',
    'score' => '用户评分',
    'num'=>'评分人数',
    'average' => '平均得分',
    'extra' => '其他评分',
    'total' => '总分',
    ];


$config['stat_opts'] = [
    'equipments_count' => [
        'name' => '仪器台数',
        'const' => TRUE,
        'weight' => 50,
        ],
    'equipments_value' => [
        'name' => '仪器总值',
        'const' => TRUE,
        'weight' => 60,
        ],
    'record_sample' => [
        'name' => '测样数',
        'weight' => 70,
        ],
    'time_total' => [
        'name' => '使用机时',
        'weight' => 80,
        ],
    'time_open' => [
        'name' => '开放机时',
        'weight' => 90,
        ],
    'time_valid' => [
        'name' => '有效机时',
        'weight' => 100,
        ],
    'use_time' => [
        'name' => '使用次数',
        'weight' => 110,
        ],
    'total_trainees' => [
        'name' => '培训人数',
        'weight' => 120,
        ],
    'pubs' => [
        'name' => '论文数',
        'weight' => 130,
        ],
    'charge_total' => [
        'name' => '使用收费',
        'weight' => 140,
        ]
    ];

$config['lock.time']['value'] = 3;
$config['lock.time']['format'] = 'm';


//统计教学机时使用
$config['cal_des'] = '教学项目';
$config['cal_type'] = '3';			


//以下为API中的相关辅助配置
$config['start_year'] = '1970';
$config['end_year'] = NULL;

$config['start_month'] = '9';
$config['end_month'] = '7';

$config['api.high_equipments_sql'] = 'SELECT equipment_id AS id, SUM( dtend - dtstart ) AS time
FROM eq_record AS eq
JOIN equipment AS e ON eq.equipment_id = e.id
WHERE e.status < 2
AND eq.dtstart >= %dtstart
AND eq.dtstart <= %dtend
GROUP BY equipment_id
ORDER BY time DESC
LIMIT 0, %num';

//以下为评分相关配置
$config['perf_opts'] = [
		'sample' => [
			'name'=>'测样',
			'record_sample'=>'测样数'
		],
		'time'=>[
			'name'=>'机时',
			'time_total' => '使用机时',
			'time_open' => '开放机时'
		],
		'use_time' => '使用次数',
		'total_trainees' => '培训人数',
		'achievements'=>[
			'name'=>'成果',
			'top3_pubs' => '三大检索论文',
			'core_pubs' => '核心刊物论文',
			'national_awards' => '国家级获奖',
			'provincial_awards' => '省部级获奖',
			'teacher_patents' => '教师专利',
			'student_patents' => '学生专利'
		],
		'projects'=>[
			'name'=>'实验项目',
			'projects_teaching' => '教学实验类项目',
			'projects_research' => '科研项目',
			'projects_public_service' => '社会服务项目'
		]
	];

$config['score_values'] = [
	'10'=>'10分',
	'100'=>'100分'
];

$config['special_list'] = [
	'equipments_value' => ['currency' => TRUE, ],
	'charge_total' => ['currency' => TRUE, ],
];

//formula 评分时使用
$config['formula.time'][] = 'time_total';
$config['formula.time'][] = 'time_open';
$config['formula.time'][] = 'time_valid';
