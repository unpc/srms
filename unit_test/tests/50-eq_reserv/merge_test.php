<?php

/*
 * @file merge_test.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 仪器预约模块仪器预约块合并功能测试脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_reserv/merge_test
 */
require_once 'merge_reserv.php';

$test = new Test_Merge_Reserv();
$test->init();

$test->assert(
    '在一个预约块的后面新添加一个预约',
    [
        'com1'=>[
            'dtstart'=>$test->time(1),
            'dtend'  =>$test->time(2),
        ],
    ],
    '$com = $this->new_com(2.5,3);$com->save();',
    [
        'com1'=>[
            'dtend'=>$test->time(3),
        ]
    ],
    TRUE
);

$test->assert(
    '在一个预约块的前面新添加一个预约',
    [
        'com1'=>[
            'dtstart'=>$test->time(3),
            'dtend'  =>$test->time(4),
        ],
    ],
    '$com = $this->new_com(2,2.5);$com->save();',
    [
        'com1'=>[
            'dtstart'=>$test->time(2),
        ]
    ],
    TRUE
);

$test->assert(
    '在一个使用预约的后面新添加一个预约',
    [
        'com1'=>[
            'dtstart'=>$test->time(-1),
            'dtend'  =>$test->time(1),
            'using'  =>TRUE,
        ],
    ],
    '$com = $this->new_com(1.5,2);$com->save();',
    [
        'com1'=>[
        ]
    ]
);
 
$test->assert(
    '在一个使用中的预约前面添加一个预约',
    [
        'com1'=>[
            'dtstart'=>$test->time(-1),
            'dtend'  =>$test->time(1),
            'using'  =>TRUE,
        ],
    ],
    '$com = $this->new_com(-2,-1.5);$com->save();',
    [
        'com1'=>[
        ]
    ]
);

$test->assert(
    '在一个有锁定记录的预约的后面添加一个预约',
    [
        'com1'=>[
            'dtstart'=>$test->time(-4),
            'dtend'  =>$test->time(-3),
            'lock'  =>TRUE,
        ],
    ],
    '$com = $this->new_com(-2.5,-1);$com->save();',
    [
        'com1'=>[
        ]
    ]
);

$test->assert(
    '在一个有锁定记录的预约的后面添加一个预约',
    [
        'com1'=>[
            'dtstart'=>$test->time(-2),
            'dtend'  =>$test->time(-1),
            'lock'  =>TRUE,
        ],
    ],
    '$com = $this->new_com(-4,-2.5);$com->save();',
    [
        'com1'=>[
        ]
    ]
);

$test->assert(
    '两个预约块，修改前一个的结束时间导致合并',
    [
        'com1'=>[
            'dtstart'=>$test->time(-4),
            'dtend'  =>$test->time(-3),
        ],
        'com2'=>[
            'dtstart'=>$test->time(-2),
            'dtend'  =>$test->time(-1),
        ],
    ],
    '$com1->dtend += 60*60*0.5;$com1->save();',
    [
        'com1'=>NULL,
        'com2'=>[
            'dtstart'=>$test->time(-4),
        ],
    ],
    TRUE
);

$test->assert(
    '两个预约块，修改后一个的开始时间导致合并',
    [
        'com1'=>[
            'dtstart'=>$test->time(-4),
            'dtend'  =>$test->time(-3),
        ],
        'com2'=>[
            'dtstart'=>$test->time(-2),
            'dtend'  =>$test->time(-1),
        ],
    ],
    '$com2->dtstart -= 60*60*0.5;$com2->save();',
    [
        'com1'=>[
            'dtend'=>$test->time(-1),
        ],
        'com2'=>NULL,
    ],
    TRUE
);
 
$test->assert(
    '两个预约块，前面一个预约使用中，修改后面一个预约，导致合并',
    [
        'com1'=>[
            'dtstart'=>$test->time(-1),
            'dtend'  =>$test->time(1),
            'using'  => TRUE,
        ],
        'com2'=>[
            'dtstart'=>$test->time(2),
            'dtend'  =>$test->time(3),
        ],
    ],
    '$com2->dtstart -= 60*60*0.5;$com2->save();',
    ['com1'=>[],'com2'=>['dtstart'=>$test->time(1.5)]],
    TRUE
);

$test->assert(
    '两个预约块，前面一个预约使用中，修改前面一个预约，导致合并',
    [
        'com1'=>[
            'dtstart'=>$test->time(-1),
            'dtend'  =>$test->time(1),
            'using'  => TRUE,
        ],
        'com2'=>[
            'dtstart'=>$test->time(2),
            'dtend'  =>$test->time(3),
        ],
    ],
    '$com1->dtend += 60*60*0.5;$com1->save();',
    ['com1'=>['dtend'=>$test->time(1.5)],'com2'=>[]],
    TRUE
);
/*
 * 此种情况就是修改使用中预约的dtstart，正常使用中不会发生。
 * 且，模拟此种情况的逻辑比较复杂，古不进行测试。
$test->assert(
    '两个预约块，后面一个预约使用中，修改后面一个预约，导致合并',
    array(
        'com1'=>array(
            'dtstart'=>$test->time(-3),
            'dtend'  =>$test->time(-2),
        ),
        'com2'=>array(
            'dtstart'=>$test->time(-1),
            'dtend'  =>$test->time(1),
            'using'  => TRUE,
        ),
    ),
    '$com2->dtstart -= 60*60*0.5;$com2->save();',
    array('com1'=>array(),'com2'=>array('dtstart'=>$test->time(-1)))
);
 */

$test->assert(
    '两个预约块，后面一个预约使用中，修改前面一个预约，导致合并',
    [
        'com1'=>[
            'dtstart'=>$test->time(-3),
            'dtend'  =>$test->time(-2),
        ],
        'com2'=>[
            'dtstart'=>$test->time(-1),
            'dtend'  =>$test->time(1),
            'using'  => TRUE,
        ],
    ],
    '$com1->dtend += 60*60*0.5;$com1->save();',
    ['com1'=>['dtend'=>$test->time(-1.5)],'com2'=>[]],
    TRUE
);

$test->assert(
    '两个预约块，前面一个预约的记录锁定中，修改前一个',
    [
        'com1'=>[
            'dtstart'=>$test->time(-4),
            'dtend'  =>$test->time(-3),
            'lock'   =>TRUE,
        ],
        'com2'=>[
            'dtstart'=>$test->time(-2),
            'dtend'  =>$test->time(-1),
        ],
    ],
    '$com1->dtend += 60*60*0.5;$com1->save;',
    ['com1'=>[],'com2'=>[]]
);

$test->assert(
    '两个预约块，前面一个预约的记录锁定中，修改后一个',
    [
        'com1'=>[
            'dtstart'=>$test->time(-4),
            'dtend'  =>$test->time(-3),
            'lock'   =>TRUE,
        ],
        'com2'=>[
            'dtstart'=>$test->time(-2),
            'dtend'  =>$test->time(-1),
        ],
    ],
    '$com2->dtstart -= 60*60*0.5;$com2->save;',
    ['com1'=>[],'com2'=>[]]
);

$test->assert(
    '两个预约块，后面一个预约的记录锁定中，修改前一个',
    [
        'com1'=>[
            'dtstart'=>$test->time(-4),
            'dtend'  =>$test->time(-3),
        ],
        'com2'=>[
            'dtstart'=>$test->time(-2),
            'dtend'  =>$test->time(-1),
            'lock'   =>TRUE,
        ],
    ],
    '$com1->dtend += 60*60*0.5;$com1->save;',
    ['com1'=>[],'com2'=>[]]
);

$test->assert(
    '两个预约块，后面一个预约的记录锁定中，修改后一个',
    [
        'com1'=>[
            'dtstart'=>$test->time(-4),
            'dtend'  =>$test->time(-3),
            'lock'   =>TRUE,
        ],
        'com2'=>[
            'dtstart'=>$test->time(-2),
            'dtend'  =>$test->time(-1),
        ],
    ],
    '$com2->dtstart -= 60*60*0.5;$com2->save;',
    ['com1'=>[],'com2'=>[]]
);

$test->assert(
	'两个预约，中间添加一个新的预约',
	[
		'com1'=>[
			'dtstart'=>$test->time(-5),
			'dtend'  =>$test->time(-4),
		],
		'com2'=>[
			'dtstart'=>$test->time(-2),
			'dtend'=>$test->time(-1),
		],
	],
	'$com = $this->new_com(-3.5,-2.5);$com->save();',
	[
		'com1'=>[
			'dtend'=>$test->time(-1),
		]
	],
    TRUE
);

$test->assert(
	'两个预约，中间添加一个新的预约，前面一个预约使用中',
	[
		'com1'=>[
			'dtstart'=>$test->time(-1),
			'dtend'  =>$test->time(1),
			'using'  =>TRUE,
		],
		'com2'=>[
			'dtstart'=>$test->time(3),
			'dtend'  =>$test->time(4),
		],
	],
	'$com = $this->new_com(1.5,2.5);$com->save();',
	[
		'com1'=>[
		],
		'com2'=>[
			'dtstart'=>$test->time(1.5),
		],
	],
    TRUE
);

$test->assert(
	'两个预约，中间添加一个新的预约，后面一个预约使用中',
	[
		'com1'=>[
			'dtstart'=>$test->time(-4),
			'dtend'  =>$test->time(-3),
			'using'  =>TRUE,
		],
		'com2'=>[
			'dtstart'=>$test->time(-1),
			'dtend'  =>$test->time(1),
			'using'  =>TRUE,
		],
	],
	'$com = $this->new_com(-2.5,-1.5);$com->save();',
	[
		'com1'=>[
			'dtend'=>$test->time(-1.5),
		],
		'com2'=>[
		],
	],
    TRUE
);

$test->assert(
	'两个预约，中间添加一个新的预约，前面一个预约的记录锁定中',
	[
		'com1'=>[
			'dtstart'=>$test->time(-5),
			'dtend'  =>$test->time(-4),
			'lock'   =>TRUE,
		],
		'com2'=>[
			'dtstart'=>$test->time(-2),
			'dtend'  =>$test->time(-1),
		],
	],
	'$com = $this->new_com(-3.5,-2.5);$com->save();',
	[
		'com1'=>[],
		'com2'=>['dtstart'=>$test->time(-3.5)],
	],
    TRUE
);

$test->assert(
	'两个预约，中间添加一个新的预约，后面一个预约的记录锁定中',
	[
		'com1'=>[
			'dtstart'=>$test->time(-5),
			'dtend'  =>$test->time(-4),
		],
		'com2'=>[
			'dtstart'=>$test->time(-2),
			'dtend'  =>$test->time(-1),
			'lock'   =>TRUE,
		],
	],
	'$com = $this->new_com(-3.5,-2.5);$com->save();',
	[
		'com1'=>['dtend'=>$test->time(-2.5)],
		'com2'=>[],
	],
    TRUE
);

$test->assert(
	'两个预约，中间添加一个新的预约，前后预约的记录都锁定中',
	[
		'com1'=>[
			'dtstart'=>$test->time(-5),
			'dtend'  =>$test->time(-4),
			'lock'   =>TRUE,
		],
		'com2'=>[
			'dtstart'=>$test->time(-2),
			'dtend'  =>$test->time(-1),
			'lock'   =>TRUE,
		],
	],
	'$com = $this->new_com(-3.5,-2.5);$com->save();',
	[
		'com1'=>[],
		'com2'=>[],
	]
);

$test->tear_down();
