<?php
    /*
     * file 20-record.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013/07/10
     *
     * useage  Q_ROOT_PATH=~/lims2/ SITE_ID=cf LAB_ID=ut php test.php ../tests/50-eq_charge/lua/20-record
     * brief 进行送样的lua脚本测试
     */
require_once(ROOT_PATH. 'unit_test/helpers/environment.php');

echo "开始环境自动生成:50-eq_charge\\record\\lua \n\n";
require_once('lua_test.php');

//环境初始化
LUA_Test::init_site();

$ROOT_USER = O('user', 1);
Cache::L('ME', $ROOT_USER);
$test_case = [
    [
        'title'=> '每小时10元的测试，使用12小时，无开机费',
        'charge_type'=> 'record_time',
        'record'=> [
            'user'=> '测试用户',
            'equipment'=> 'record测试仪器',
            'dtstart'=> '0,0,0,1,2,2012',
            'dtend'=> '11,59,59,1,2,2012',
            'samples'=> 1
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 10,
                'minimum_fee'=> 0
            ]
        ],
        'assert'=> 120
    ],
    [
        'title'=> '每小时100元的测试，使用5.5小时，开机费10元',
        'charge_type'=> 'record_time',
        'record'=> [
            'user'=> '测试用户',
            'equipment'=> 'record测试仪器',
            'dtstart'=> '0,0,0,1,3,2012',
            'dtend'=> '5,29,59,1,3,2012',
            'samples'=> 1
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 100,
                'minimum_fee'=> 10
            ]
        ],
        'assert'=> 560
    ],
    [
        'title'=> '每个测样10元的测试，测试5个样品，开机费100元',
        'charge_type'=> 'record_samples',
        'record'=> [
            'user'=> '测试用户',
            'equipment'=> 'record测试仪器',
            'dtstart'=> '0,0,0,1,4,2012',
            'dtend'=> '5,30,0,1,4,2012',
            'samples'=> 5
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 10,
                'minimum_fee'=> 100
            ]
        ],
        'assert'=> 150
    ],
    [
        'title'=> '每个测样4.8元的测试，测试200个样品，开机费50元',
        'charge_type'=> 'record_samples',
        'record'=> [
            'user'=> '测试用户',
            'equipment'=> 'record测试仪器',
            'dtstart'=> '0,0,0,1,5,2012',
            'dtend'=> '5,30,0,1,5,2012',
            'samples'=> 200
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 4.8,
                'minimum_fee'=> 50
            ]
        ],
        'assert'=> 960 + 50
    ],
    [
        'title'=> '每次使用100元，开机费50元',
        'charge_type'=> 'record_times',
        'record'=> [
            'user'=> '测试用户',
            'equipment'=> 'record测试仪器',
            'dtstart'=> '0,0,0,1,6,2012',
            'dtend'=> '5,30,0,1,6,2012',
            'samples'=> 200
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 100,
                'minimum_fee'=> 50
            ]
        ],
        'assert'=> 150
    ],
    [
        'title'=> 'tags测试', 
        'charge_type'=> 'record_times',
        'record'=> [
            'user'=> '测试用户',
            'equipment'=> 'record测试仪器',
            'dtstart'=> '0,0,0,1,7,2012',
            'dtend'=> '6,0,0,1,7,2012',
            'samples'=> 1,
        ],
        'user_tags'=> [
            'VIP'=> [
                'users'=> '测试用户',
            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 100,
                'minimum_fee'=> 50,
            ],
            'VIP'=> [
                'unit_price'=> 10,
                'minimum_fee'=> 0
            ]
        ],
        'assert'=> 10
    ]
];

LUA_Test::title('使用记录相关测试');

foreach($test_case as $case) {
    //创建user
    $user = O('user', ['name'=> $case['record']['user']]);

    if (!$user->id) {
        $user = LUA_Test::add_user($case['record']['user']);
    }
    //创建equipment
    $equipment = O('equipment', ['name'=> $case['record']['equipment']]);

    if (!$equipment->id) {
        $equipment = LUA_Test::add_equipment($case['record']['equipment'], $ROOT_USER, $ROOT_USER);
        LUA_Test::make_billing_environment($equipment);
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

            $users = (string) $content['users'];
            $groups = (string) $content['groups'];
            $labs = (string) $content['labs'];

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

    $equipment->accept_sample = TRUE;
    $equipment->charge_template = [
        'record'=> $case['charge_type']
    ];

    $root = $equipment->get_root();

    if (isset($case['config'])) {

        $config = [];

        foreach($case['config'] as $name => $sub_config) {
            $config[$name] = [
                'unit_price'=> $sub_config['unit_price'],
                'minimum_fee'=> $sub_config['minimum_fee']
            ];
        }

        $params = EQ_Charge::array_p2l($config);
        EQ_Charge::update_charge_script($equipment, 'record',['%options'=>$params]);
        EQ_Charge::put_charge_setting($equipment, 'record', $config);
    }

    $equipment->save();

    $record = O('eq_record');
    $record->user = $user;
    $record->equipment = $equipment;
    $record->samples = $case['record']['samples'];
    $record->dtstart = call_user_func_array('mktime', explode(',', $case['record']['dtstart']));
    $record->dtend = call_user_func_array('mktime', explode(',', $case['record']['dtend']));
    $record->save();

    $charge = O('eq_charge', ['source'=>$record]);

    LUA_Test::set_object($charge);

    LUA_Test::assert($case['title'], LUA_Test::fee() == $case['assert']);
}

LUA_Test::title('完成');
