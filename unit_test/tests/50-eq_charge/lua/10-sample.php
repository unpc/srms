<?php
    /*
     * file 10-sample.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013/07/10
     *
     * useage  Q_ROOT_PATH=~/lims2/ SITE_ID=cf LAB_ID=ut php test.php ../tests/50-eq_charge/lua/10-sample
     * brief 进行送样的lua脚本测试
     */
require_once(ROOT_PATH. 'unit_test/helpers/environment.php');

echo "开始环境自动生成:50-eq_charge\\sample\\lua \n\n";
require_once('lua_test.php');

//环境初始化
LUA_Test::init_site();

$ROOT_USER = O('user', 1);

Cache::L('ME', $ROOT_USER);

$test_case = [
    [
        'title'=> '每个样品1元开机费0元的测试',
        'sample'=> [
            'sender'=> '测试用户',
            'equipment'=> 'sample测试仪器',
            'dtsubmit'=> 0,
            'dtpickup'=> 0,
            'dtstart'=> 0,
            'dtend'=> 0,
            'count'=> 20
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 1,
                'minimum_fee'=> 0
            ]
        ],
        'assert'=> 20
    ],
    [
        'title'=> '每个样品1元开机费100元的测试',
        'sample'=> [
            'sender'=> '测试用户',
            'equipment'=> 'sample测试仪器',
            'dtsubmit'=> 0,
            'dtpickup'=> 0,
            'dtstart'=> 0,
            'dtend'=> 0,
            'count'=> 20
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 1,
                'minimum_fee'=> 100
            ]
        ],
        'assert'=> 120
    ],
    [
        'title'=> '每个样品2.5元开机费0元的测试',
        'sample'=> [
            'sender'=> '测试用户',
            'equipment'=> 'sample测试仪器',
            'dtsubmit'=> 0,
            'dtpickup'=> 0,
            'dtstart'=> 0,
            'dtend'=> 0,
            'count'=> 40
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 2.5,
                'minimum_fee'=> 0
            ]
        ],
        'assert'=> 100
    ],
    [
        'title'=> '每个样品2.5元开机费200元的测试',
        'sample'=> [
            'sender'=> '测试用户',
            'equipment'=> 'sample测试仪器',
            'dtsubmit'=> 0,
            'dtpickup'=> 0,
            'dtstart'=> 0,
            'dtend'=> 0,
            'count'=> 40
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 2.5,
                'minimum_fee'=> 200
            ]
        ],
        'assert'=> 300
    ],
    [
        'title'=> 'tags测试',
        'sample'=> [
            'sender'=> '测试用户' ,
            'equipment'=> 'sample测试仪器',
            'dtsubmit'=> 0,
            'dtpickup'=> 0,
            'dtstart'=> 0,
            'dtend'=> 0,
            'count'=> 100
        ],
        'user_tags'=> [
            'VIP'=> [
                'users'=>'测试用户'
            ]
        ],
        'config'=> [
            '*'=> [
                'unit_price'=> 1,
                'minimum_fee'=> 200,
            ],
            'VIP'=> [
                'unit_price'=> 0.5,
                'minimum_fee'=> 10
            ]
        ],
        'assert'=> 50 + 10
    ]
];

LUA_Test::title('送样相关测试');

foreach($test_case as $case) {

    //创建sender
    $sender = O('user', ['name'=> $case['sample']['sender']]);

    if (!$sender->id) {
        $sender = LUA_Test::add_user($case['sample']['sender']);
    }

    //创建equipment
    $equipment = O('equipment', ['name'=> $case['sample']['equipment']]);

    if (!$equipment->id) {
        $equipment = LUA_Test::add_equipment($case['sample']['equipment'], $ROOT_USER, $ROOT_USER);
       
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

            foreach(explode(',', $users) as $user) {
                $_user = O('user', ['name'=> $user]);
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
        'sample'=> 'sample_count'
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
        EQ_Charge::update_charge_script($equipment, 'sample',['%options'=>$params]);
        EQ_Charge::put_charge_setting($equipment, 'sample', $config);
    }

    $equipment->save();

    $sample = O('eq_sample');
    $sample->sender = $sender;
    $sample->equipment = $equipment;
    $sample->status = EQ_Sample_Model::STATUS_APPROVED;
    $sample->count = $case['sample']['count'];
    $sample->dtstart = $case['sample']['dtstart'];
    $sample->dtend = $case['sample']['dtend'];
    $sample->dtsubmit = $case['sample']['dtsubmit'];
    $sample->dtpickup = $case['sample']['dtpickup'];
    $sample->save();

    $charge = O('eq_charge', ['source'=>$sample]);

    LUA_Test::set_object($charge);

    LUA_Test::assert($case['title'], LUA_Test::fee() == $case['assert']);

}


LUA_Test::title('完成');
