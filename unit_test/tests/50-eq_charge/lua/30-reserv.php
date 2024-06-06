<?php
    /*
     * file 30-eq_reserv.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013/07/10
     *
     * useage  Q_ROOT_PATH=~/lims2/ SITE_ID=cf LAB_ID=ut php test.php ../tests/50-eq_charge/lua/30-eq_reserv
     * brief 进行送样的lua脚本测试
     */
require_once(ROOT_PATH. 'unit_test/helpers/environment.php');

echo "开始环境自动生成:50-eq_charge\\reserv\\lua \n\n";
require_once('lua_test.php');

//环境初始化
LUA_Test::init_site();

$ROOT_USER = O('user', 1);
Cache::L('ME', $ROOT_USER);
$test_case = [
    [
        'title'=> '情况一: 预约后预约者和普通用户均使用，收全部费用',
        'reservs'=> [
             1 =>[
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,3,2013',
                'dtend'=> '11,59,59,7,3,2013',
                'assert'=> 12 * 50 - 50 * 2
            ]
        ],
        'records'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,3,2013',
                'dtend'=> '4,0,0,7,3,2013',
                'reservs'=> 1,
                'assert'=> 0
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '6,0,0,7,3,2013',
                'dtend'=> '7,59,59,7,3,2013',
                'assert'=> (8 - 6) * 50
            ],
            3 => [
                'user'=> '测试用户1',
                'dtstart'=> '10,0,0,7,3,2013',
                'dtend'=> '11,59,59,7,3,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [
                1
            ],
            'records'=> [
                1,2,3
            ]
        ]
    ],
    [
        'title'=> '情况二: 预约后不使用，收全部费用',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,4,2013',
                'dtend'=> '11,59,59,7,4,2013',
                'assert'=> 12 * 50
            ],
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=>[
            'reservs' => [
                1
            ]
        ]
    ],
    [
        'title'=> '情况三: 预约后预约者使用，机主使用，收全部费用',
        'reservs'=> [
            1=>[
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,5,2013',
                'dtend'=> '11,59,59,7,5,2013',
                'assert'=>  12 * 50 - 50 * 2
            ],
        ],
        'records'=>[
            1=>[
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,5,2013',
                'dtend'=> '4,0,0,7,5,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
            2=>[
                'user'=> $ROOT_USER,
                'dtstart'=> '6,0,0,7,5,2013',
                'dtend'=> '7,59,59,7,5,2013',
                'assert'=> 2 * 50
            ],
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [1],
            'records'=> [1,2]
        ]
    ],
    [
        'title'=> '情况四: 预约后预约者提前使用并提前结束，收全部费用和超时收费',
        'reservs'=> [
            1=>[
                'user'=> '测试用户1',
                'dtstart'=> '12,0,0,7,6,2013',
                'dtend'=> '17,59,59,7,6,2013',
                'assert'=> (6 + 2) * 50
            ],
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '10,0,0,7,6,2013',
                'dtend'=> '16,0,0,7,6,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
        ],
        'tests'=> [
            'reservs' => [1],
            'records'=> [1]
        ]
    ],
    [
        'title'=> '情况五: 两段预约，第一段使用超时，第二段提前使用而且超时',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,7,2013',
                'dtend'=> '11,59,59,7,7,2013',
                'assert'=> (12 + 1)* 50
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '16,0,0,7,7,2013',
                'dtend'=> '17,59,59,7,7,2013',
                'assert'=> 2 * 50 + 2 * 50 + 2 * 50
            ]
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '10,0,0,7,7,2013',
                'dtend'=> '12,59,59,7,7,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '14,0,0,7,7,2013',
                'dtend'=> '19,59,59,7,7,2013',
                'reserv'=> 2,
                'assert' => 0
            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [1,2],
            'records'=> [1,2]
        ]
    ],
    [
        'title'=> '情况六: 两段预约，第一段使用超时，第二段未使用，被他人占用',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,8,2013',
                'dtend'=> '9,59,59,7,8,2013',
                'assert'=> 10 *  50 + 4 * 50,
            ],
            2 => [
                'user'=> '测试用户1',
                'dtstart'=> '12,0,0,7,8,2013',
                'dtend'=> '19,59,59,7,8,2013',
                'assert'=> 6 * 50 - 2 * 50,
            ]
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '2,0,0,7,8,2013',
                'dtend'=> '13,59,59,7,8,2013',
                'reserv'=> 1
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '16,0,0,7,8,2013',
                'dtend'=> '17,59,59,7,8,2013',
                'reserv'=> 2
            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [1,2],
            'records'=> [1,2]
        ]
    ],
    [
        'title'=> '情况七: 两段预约，第一段用户1预约并超时使用到用户二的预约范围，第二段用户二预约正常使用未超时',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,9,2013',
                'dtend'=> '10,0,0,7,9,2013',
                'assert'=>  10 * 50 + 4 * 50
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '12,0,0,7,9,2013',
                'dtend'=> '19,59,59,7,9,2013',
                'assert'=> 6 * 50
            ]
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,9,2013',
                'dtend'=> '13,59,59,7,9,2013',
                'reserv'=> 1
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '16,0,0,7,9,2013',
                'dtend'=> '18,0,0,7,9,2013',
                'reserv'=> 2
            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=>[
            'reservs'=> [1,2],
            'records'=> [1,2]
        ]
    ],
    [
        'title'=> '情况八: 两段预约，第一段用户1预约使用不超时，中间管理员使用，占用第一段预约的后半段和第二段预约的前半段, 第二段用户二预约正常使用并超时',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,10,2013',
                'dtend'=> '10,0,0,7,10,2013',
                'assert'=>  10 * 50 - 2 * 50
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '12,0,0,7,10,2013',
                'dtend'=> '18,0,0,7,10,2013',
                'assert'=> 6 * 50
            ]
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,10,2013',
                'dtend'=> '6,0,0,7,10,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
            2 => [
                'user'=> $ROOT_USER,
                'dtstart'=> '8,0,0,7,10,2013',
                'dtend'=> '13,59,59,7,10,2013',
                'assert'=> 6 * 50
            ],
            3 => [
                'user'=> '测试用户2',
                'dtstart'=> '16,0,0,7,10,2013',
                'dtend'=> '19,59,59,7,10,2013',
                'reserv'=> 2,
                'assert'=> 0
            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [1,2],
            'records'=>[1,2,3]
        ]
    ],
    [
        'title'=> '情况九: 用户1预约使用后用户二使用，并且用户一不再使用',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,11,2013',
                'dtend'=> '11,59,59,7,11,2013',
                'assert'=> 12 * 50 - 2 * 50
            ],
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,11,2013',
                'dtend'=> '6,0,0,7,11,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '8,0,0,7,11,2013',
                'dtend'=> '9,59,59,7,11,2013',
                'assert'=> 100
            ],
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [1],
            'records'=> [1,2]
        ]
    ],
    [
        'title'=> '情况十: 两段预约，第一段用户1预约使用不超时，中间管理员使用，占用第一段预约的后半段和第二段预约的前半段, 第二段用户二预约正常使用未超时',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,12,2013',
                'dtend'=> '9,59,59,7,12,2013',
                'assert'=>  10 * 50 - 2 * 50
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '12,0,0,7,12,2013',
                'dtend'=> '19,59,59,7,12,2013',
                'assert'=> 6 * 50
            ]
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,12,2013',
                'dtend'=> '6,0,0,7,12,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
            2 => [
                'user'=> $ROOT_USER,
                'dtstart'=> '8,0,0,7,12,2013',
                'dtend'=> '13,59,59,7,12,2013',
                'assert'=> 300
            ],
            3 => [
                'user'=> '测试用户2',
                'dtstart'=> '16,0,0,7,12,2013',
                'dtend'=> '18,0,0,7,12,2013',
                'reserv'=> 2,
                'assert'=> 0

            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [1,2],
            'records'=> [1,2,3]
        ]
    ],
    [
        'title'=> '情况十一: 两段预约，第一段用户1预约使用不超时, 第二段用户二预约正常提前使用未超时',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,13,2013',
                'dtend'=> '10,0,0,7,13,2013',
                'assert'=>  10 * 50 - 2 * 50
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '12,0,0,7,13,2013',
                'dtend'=> '19,59,59,7,13,2013',
                'assert'=> 12 * 50
            ]
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,13,2013',
                'dtend'=> '6,0,0,7,13,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '8,0,0,7,13,2013',
                'dtend'=> '18,0,0,7,13,2013',
                'reserv'=> 2,
                'assert'=> 0
            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [1,2],
            'records'=> [1,2]
        ]
    ],
    [
        'title'=> '情况十二: 一段预约，用户1、用户2、用户3分别使用不超时',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,14,2013',
                'dtend'=> '9,59,59,7,14,2013',
                'assert'=> 10 * 50 - 2 * 50 * 2
            ],
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,14,2013',
                'dtend'=> '2,0,0,7,14,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '3,0,0,7,14,2013',
                'dtend'=> '4,59,59,7,14,2013',
                'assert'=> 100
            ],
            3 => [
                'user'=> '测试用户3',
                'dtstart'=> '6,0,0,7,14,2013',
                'dtend'=> '7,59,59,7,14,2013',
                'assert'=> 100
            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [1],
            'records'=>[1,2,3]
        ]
    ],
    [
        'title'=> '情况十三: 一段预约，用户1、用户2、用户3分别使用不超时',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,15,2013',
                'dtend'=> '4,0,0,7,15,2013',
                'assert'=> (8 - 4) * 50 + (4 - 0) * 50
            ],
            2 => [
                'user'=> '测试用户1',
                'dtstart'=> '6,0,0,7,15,2013',
                'dtend'=> '17,59,59,7,15,2013',
                'assert'=> (12 - 2) * 50 - 2 * 50
            ],
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,15,2013',
                'dtend'=> '7,59,59,7,15,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '10,0,0,7,15,2013',
                'dtend'=> '11,59,59,7,15,2013',
                'assert' => 100
            ],
            3 => [
                'user'=> '测试用户1',
                'dtstart'=> '14,0,0,7,15,2013',
                'dtend'=> '16,0,0,7,15,2013',
                'reserv'=> 2,
                'assert'=>0
            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [1,2],
            'records'=> [1,2,3]
        ]
    ],
    [
        'title'=> '情况十四: 两段预约，第一条使用不超时，第二条提前使用，提前结束，第三条正常使用不超时',
        'reservs'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,16,2013',
                'dtend'=> '6,0,0,7,16,2013',
                'assert'=> 6 * 50 - 2 * 50
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '8,0,0,7,16,2013',
                'dtend'=> '15,59,59,7,16,2013',
                'assert'=> (16 - 8) * 50 + (8 - 4) * 50
            ],
        ],
        'records'=>[
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,16,2013',
                'dtend'=> '2,0,0,7,16,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '4,0,0,7,16,2013',
                'dtend'=> '10,0,0,7,16,2013',
                'reserv'=> 2,
                'assert'=> 0
            ],
            3 => [
                'user'=> '测试用户2',
                'dtstart'=> '12,0,0,7,16,2013',
                'dtend'=> '14,0,0,7,16,2013',
                'reserv'=> 2,
                'assert'=> 0
            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 50,
                'minimum_fee'=> 0
            ]
        ],
        'tests'=> [
            'reservs'=> [1,2],
            'records'=> [1,2,3]
        ]
    ],
    [
        'title'=> 'tag测试',
        'reservs'=> [
             1 =>[
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,17,2013',
                'dtend'=> '11,59,59,7,17,2013',
                'assert'=> 2000 + 10 * 12 - 2 * 10
            ]
        ],
        'records'=> [
            1 => [
                'user'=> '测试用户1',
                'dtstart'=> '0,0,0,7,17,2013',
                'dtend'=> '4,0,0,7,17,2013',
                'reservs'=> 1,
                'assert'=> 0
            ],
            2 => [
                'user'=> '测试用户2',
                'dtstart'=> '6,0,0,7,17,2013',
                'dtend'=> '7,59,59,7,17,2013',
                'assert'=> (8 - 6) * 10 + 100
            ],
            3 => [
                'user'=> '测试用户1',
                'dtstart'=> '10,0,0,7,17,2013',
                'dtend'=> '11,59,59,7,17,2013',
                'reserv'=> 1,
                'assert'=> 0
            ],
        ],
        'user_tags'=>  [
            'VIP'=> [
                'users'=> '测试用户1,测试用户3'
            ],
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 10,
                'minimum_fee'=> 100,
            ],
            'VIP'=> [
                'unit_price'=> 10,
                'minimum_fee'=> 2000,
            ],
        ],
        'tests'=> [
            'reservs'=> [
                1
            ],
            'records'=> [
                1,2,3
            ]
        ]
    ]
];

LUA_Test::title('使用预约相关测试');

foreach($test_case as $case) {

    //创建equipment
    $equipment = O('equipment', ['name'=> '预约测试仪器']);

    if (!$equipment->id) {
        $equipment = LUA_Test::add_equipment('预约测试仪器', $ROOT_USER, $ROOT_USER);
        $lab = LUA_Test::make_billing_environment($equipment);
    }

    //设定需要预约
    $equipment->accept_reserv = TRUE;

    $equipment->charge_template = [
        'reserv'=> 'time_reserv_record',
    ];

    $equipment->save();

    if (isset($case['config'])) {

        $root = $equipment->get_root();

        $config = [];

        foreach($case['config'] as $name => $sub_config) {
            $config[$name] = [
                'unit_price'=> $sub_config['unit_price'],
                'minimum_fee'=> $sub_config['minimum_fee']
            ];

            $params = EQ_Charge::array_p2l($config);
            EQ_Charge::update_charge_script($equipment, 'reserv',['%options'=>$params]);
            EQ_Charge::put_charge_setting($equipment, 'reserv', $config);
        }

    }

    $equipment->save();

    //创建预约calendar
    $calendar = O('calendar', [
        'parent'=> $equipment ,
        'type'=> 'eq_reserv'
    ]);

    if (!$calendar->id) {
        $calendar = O('calendar');
        $calendar->parent = $equipment;
        $calendar->type = 'eq_reserv';
        $calendar->name = I18N::T('eq_reserv', '%equipment的预约', ['%equipment' => $equipment->name]);
        $calendar->save();
    }

    $lua_reserv_arr = [];

    foreach($case['reservs'] as $key=>$r1) {

        //创建user
        $user = O('user', ['name'=> $r1['user']]);

        if (!$user->id) {
            $user = LUA_Test::add_user($r1['user']);
        }

        //component创建后会自动创建eq_reserv对象，无需再专门创建eq_reserv
        $component = O('cal_component');
        $component->organizer = $user;
        $component->calendar = $calendar;
        $component->dtstart = call_user_func_array('mktime', explode(',', $r1['dtstart']));
        $component->dtend = call_user_func_array('mktime', explode(',', $r1['dtend']));

        $component->save();

        $lua_reserv_arr[$key] = [
            'reserv'=>Q("$component eq_reserv.component")->current(),
            'assert'=> $r1['assert']
        ];
    }

    //创建record
    if (isset($case['records'])) {

        foreach($case['records'] as $key => $r2) {
            $record = O('eq_record');
            $record->equipment = $equipment;

            if (isset($r2['user'])) {
                if ($r2['user'] instanceof User_Model) {

                    $record->user = $r2['user'];
                }
                else {
                    $user = O('user', [
                        'name'=> $r2['user']
                    ]);

                    if (!$user->id) {
                        $user = LUA_Test::add_user($r2['user']);
                        //$user->lab = $lab;
                    }

                    $record->user = $user;
                }
            }
            else {
                $record->user = $user;
            }

            $record->reserv = $r2['reserv'] ? $lua_reserv_arr[$r2['reserv']]['reserv'] : current($lua_reserv_arr);

            $record->dtstart = call_user_func_array('mktime', explode(',', $r2['dtstart']));
            $record->dtend = call_user_func_array('mktime', explode(',', $r2['dtend']));
            $record->save();

            $lua_record_arr[$key] = [
                'record'=> $record,
                'assert'=> $r2['assert']
            ];
        }
    }

    //创建tag
    if (isset($case['user_tags'])) {

        foreach($case['user_tags'] as $name=> $content) {
            $root = $equipment->get_root();
            $tag = O('tag', ['root'=> $root, 'name'=> $name]);

            if (!$tag->id) {
                $tag->name = $name;
                $tag->parent = $root;

                $tags = Q("tag[root={$root}]:sort(weight D)");

                if (!count($tags)) {
                    $tag->weight = 0;
                }
                else {
                    $tag->weight = $tags->current()->weight + 1;
                }

                $tag->update_root()->save();
            }

            $users = $content['users'];
            $groups = $content['groups'];
            $labs = $content['labs'];

            foreach(explode(',', $users) as $u) {
                $_user = O('user', ['name'=> $u]);
                if ($_user->id) {
                    $tag->connect($_user);
                }
            }

            foreach(explode(',', $groups) as $group) {
                $_group = O('tag', ['name'=> $group, 'root'=> Tag_Model::root('group')]);
                if ($_group->id) {
                    $tag->connect($_group);
                }
            }

            foreach(explode(',', $labs) as $lab) {
                $_lab = O('lab', ['name'=> $lab]);
                if ($_lab->id) {
                    $tag->connect($_lab);
                }
            }
        }
    }


    foreach($case['tests'] as $key => $test) {

        if ($key == 'reservs') {

            foreach($test as $k) {
                $reserv = $lua_reserv_arr[$k]['reserv'];
                $assert = $lua_reserv_arr[$k]['assert'];
                
                $charge = O('eq_charge', ['source'=>$reserv]);
                if(!$charge->id){
                    $charge->source = $reserv;
                }

                LUA_Test::set_object($charge);
                LUA_Test::assert($case['title'], round(LUA_Test::fee(), 4) == round($assert, 4));

            }
            unset($k);
        }

        if ($key == 'records') {
            foreach($test as $k) {
                $record = $lua_record_arr[$k]['record'];
                $assert = $lua_record_arr[$k]['assert'];

                $charge = O('eq_charge', ['source'=>$record]);
                 if(!$charge->id){
                    $charge->source = $record;
                }

                LUA_Test::set_object($charge);
                LUA_Test::assert($case['title'], round(LUA_Test::fee(), 4) == round($assert, 4));

            }
            unset($k);
        }
    }

    unset($lua_reserv_arr);
    unset($lua_record_arr);
}


LUA_Test::title('完成');
