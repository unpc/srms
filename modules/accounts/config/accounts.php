<?php
/*
$config['available_types']['cf'] = array(
	'title' => 'CF',
	);

$config['available_types']['cf-lite'] = array(
	'title' => 'CF-lite',
	);

$config['available_types']['cf-mini'] = array(
	'title' => 'CF-mini',
	);

$config['available_types']['lims'] = array(
	'title' => 'LIMS',
	);

$config['available_types']['mall'] = array(
	'title' => 'Mall',
	);

$config['available_types']['tender'] = array(
	'title' => 'Tender',
	);
*/

$config['available_types'] = [
	'cf' => [
		'title' => 'CF',
		'site_id' => 'cf',
		],
	'cf-lite' => [
		'title' => 'CF-lite',
		'site_id' => 'cf-lite',
		],
	'cf-mini' => [
		'title' => 'CF-mini',
		'site_id' => 'cf-mini',
		],
	'lims_online' => [
		'title' => 'LIMS 在线版',
		'site_id' => 'lab',
		],
	'lims_server' => [
		'title' => 'LIMS 服务器版',
		'site_id' => 'lab',
		],
	'mall' => [
		'title' => 'Mall',
		'site_id' => '',
		],
	'tender' => [
		'title' => 'Tender',
		'site_id' => '',
		],
	];

//默认创建站点的管理员账号、密码
$config['admin_token'] = 'genee|database';
$config['admin_password'] = '83719730';

//可用currency
$config['currency'] = [
    'dollar'=> [
        'title'=> '美元',
        'sign'=> '$',
    ],
    'RMB'=> [
        'title'=> '人民币',
        'sign'=> '¥',
    ],
];

//默认为RMB
$config['default_currency'] = 'RMB';

//语言
$config['locales'] = [
    'en_US'=> '英文',
    'zh_CN'=> '中文'
];

//默认语言
$config['default_locale'] = 'zh_CN';

//默认时区
$config['default_timezone'] = 'Asia/Shanghai';

$config['modules'] = [
    'people'=> [
        'title'=> '成员目录',
        'default'=> TRUE,
    ],
    'equipments'=> [
        'title'=> '仪器目录',
        'default'=> TRUE,
    ],
    'orders'=> [
        'title'=> '订单管理',
        'default'=> TRUE,
    ],
    'vendor'=> [
        'title'=> '供应商管理',
        'default'=> TRUE,
    ],
    'entrance'=> [
        'title'=> '门禁管理',
    ],
    'roles'=> [
        'title'=> '权限管理',
        'default'=> TRUE,
    ],
    'treenote'=> [
        'title'=> '项目管理',
        'default'=> TRUE,
    ],
    'inventory'=> [
        'title'=> '存货管理',
        'default'=> TRUE,
    ],
    'achievements'=> [
        'title'=> '成果管理',
        'default'=> TRUE,
    ],
    'envmon'=> [
        'title'=> '环境监控',
    ],
    'schedule'=> [
        'title'=> '日程管理',
        'default'=> TRUE,
    ],
    'nfs'=> [
        'title'=> '文件系统',
        'modules'=> [
            'nfs',
            'nfs_share',
        ],
        'default'=> TRUE,
    ],
    'grants'=> [
        'title'=> '经费管理',
        'default'=> TRUE,
    ],
    'messages'=> [
        'title'=> '消息中心',
        'default'=> TRUE,
    ],
    'vidmon'=> [
        'title'=> '视频监控',
    ],
    'meeting'=> [
        'title'=> '会议室',
    ],
    'wordpress'=> [
        'title'=> '主页管理',
    ],
];
