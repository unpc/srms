<?php
/*
 * @file get_block_test.php
 * @author Rui Ma <rui.ma@geneegroup.com>
 * @date 2012-10-15
 *
 * @brief 测试仪器设定块状预约时段后，是否可正常进行块状时间获取
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_reserv/get_block_test
 */

require_once(ROOT_PATH.'unit_test/helpers/environment.php');

class EQ_reserv_block_test  {

    public $equipment;
    private $db;
    private $backup_file;


    //初始化
    public function init() {
		Unit_Test::echo_text('块状预约，测试开始');
		$this->backup_file = tempnam('/tmp','database');
		$this->db = Database::factory();
		$ret = $this->db->snapshot($this->backup_file);
		Unit_Test::assert('备份数据库',$ret);


		$this->db->empty_database();
		Database::reset();
		Unit_Test::assert('清空数据库',TRUE);

        ORM_Model::destroy('role');
        ORM_Model::destroy('user');
        ORM_Model::destroy('lab');
        ORM_Model::destroy('equipment');
		
		Environment::add_user('genee');
		$user = Environment::add_user('unit_test使用者');

		$incharger = Environment::add_user('unit_test负责人');
		
		$equipment = Environment::add_equipment('Block仪器',$incharger,$incharger);

		$equipment->accept_reserv = TRUE;
        $equipment->accept_block_time = TRUE;

        //设定默认长度对齐时间
        $equipment->reserv_interval_time = 1800;

        //设定默认起始对齐时间
        $equipment->reserv_align_time = 3600;

		Unit_Test::assert('添加仪器，设定仪器长度对齐时间为1小时，起始对齐时间为30分钟', $equipment->save());
		$this->equipment = $equipment;
    }

    //assert
    public function assert($assert_title, $block_array = NULL, $assert_datas) {

        Unit_Test::echo_text("\n". $assert_title);

        //设定仪器的块状预约时段
        $data = [];
        if ($block_array) {
            foreach ($block_array as $key=>$block) {
                $data[$key] = [
                    'dtstart'=>[
                        'h'=>$block['dtstart']['h'],
                        'i'=>$block['dtend']['i']
                    ],
                    'dtend'=>[
                        'h'=>$block['dtend']['h'],
                        'i'=>$block['dtend']['i']
                    ],
                    'interval_time'=>$block['interval'],
                    'align_time'=>$block['align']
                ];
            }
        }

        $this->equipment->reserv_block_data = $data;
        $this->equipment->save();

        $reflector = new ReflectionMethod('EQ_reserv', 'get_format_block');
        $reflector->setAccessible(TRUE);

        foreach($assert_datas as $assert_data) {
            $block = $reflector->invokeArgs(NULL, [$this->equipment, $assert_data['time']]);
            Unit_Test::echo_text($assert_data['title']);
            Unit_Test::assert('比对block起始时间', $block['start'] == $assert_data['start']);
            Unit_Test::assert('比对block结束时间', $block['end'] == $assert_data['end']);
            Unit_Test::assert('比对block长度对齐时间', $block['interval'] == $assert_data['interval']);
            Unit_Test::assert('比对block起始对齐时间', $block['align'] == $assert_data['align']);
            echo "\n";
        }
    }

    public function tear_down() {
		$ret = $this->db->restore($this->backup_file);
		Unit_Test::assert('恢复数据库',$ret);
		Unit_Test::echo_text('块状预约，测试结束');
    }
}

//assert调用格式如下：
/*
 *$test->assert(
 *    'assert_title', //assert title
 *    array( //设定block时间
 *        array( //第一block
 *            'dtstart' => array( //interval_time设定
 *                'h' => '2',
 *                'i' => '0'
 *            ),
 *            'dtend' => array( //align_time设定
 *                'h' => '6',
 *                'i' =>'0',
 *            ),
 *            'interval_time'=> 3600, //起始时刻设定
 *            'align_time' => 1800//结束时刻设定
 *        ),
 *        array( //第二个block
 *            'dtstart' => array( //interval_time设定
 *                'h' => '2',
 *                'i' => '0'
 *            ),
 *            'dtend' => array( //align_time设定
 *                'h' => '6',
 *                'i' =>'0',
 *            ),
 *            'interval_time'=> 3600, //起始时刻设定
 *            'align_time' => 1800//结束时刻设定
 *        ),
 *    ),
 *    array(
 *        array( //第一个时间点测试
 *            'time' => 130000000, //时间点
 *            'start'=> 130000010, //用于与获取到的block的起始时间进行比对的时间
 *            'end'=>130000020, //用于与获取到的block的结束时间进行比对的时间
 *            'interval'=>3600, //用于与获取到的block的interval进行比对的值
 *            'align'=>1800 //用于与获取到的block的align进行比对的值
 *        ),
 *        array( //第二个时间测试点
 *            'time' => 130000000,
 *            'start'=> 130000010,
 *            'end'=>130000020,
 *            'interval'=>3600,
 *            'align'=>1800
 *        )
 *    )
 *);
 */

$test = new EQ_reserv_block_test();

//初始化
$test->init();

//设定特定测试时间
$month = 10;
$day = 5;
$year = 2012;

$time = mktime(10, 0, 0, $month, $day, $year);

$day_start = mktime(0, 0, 0, $month, $day, $year);
$day_end = $day_start + 86400;
$default_interval = $test->equipment->reserv_interval_time;
$default_align = $test->equipment->reserv_align_time;

$test->assert(
    '系统初始化获取默认的时间块获取测试',
    null, //不增加block
    [
        [
            'title'=>'默认时间block测试',
            'time'=>$time,
            'start'=>$day_start,
            'end'=>$day_end,
            'align'=>$default_align,
            'interval'=>$default_interval
        ],
        [
            'title'=>'默认时间当天起始时间获取block测试',
            'time' =>$day_start,
            'start'=>$day_start,
            'end'=>$day_end,
            'align'=>$default_align,
            'interval'=>$default_interval
        ],
        [
            'title'=>'默认时间当天结束时间获取block测试',
            'time' =>$day_end,
            'start'=>$day_end,
            'end'=>$day_end + 86400,
            'align'=>$default_align,
            'interval'=>$default_interval
        ]
    ]
);

$test->assert(
    '一个普通块获取测试，块状时间:01:00 - 06::00',
    [
        [
            'dtstart' =>[
                'h'=>1,
                'i'=>0
            ],
            'dtend'=>[
                'h'=>6,
                'i'=>0
            ],
            'interval'=>1800,
            'align'=>1800
        ]
    ],
    [
        [
            'title'=>'00:00 预约块获取测试',
            'time'=>$day_start,
            'start'=>$day_start,
            'end'=>mktime(1, 0, 0, $month, $day, $year),
            'interval'=>$default_interval,
            'align'=>$default_align
        ],
        [
            'title' => '03:00 预约块获取测试',
            'time'=>mktime(3, 0, 0, $month, $day, $year),
            'start'=>mktime(1, 0, 0, $month, $day, $year),
            'end'=>mktime(6, 0, 0, $month, $day, $year),
            'interval'=>1800,
            'align'=>1800
        ],
        [
            'title' =>'07:00 预约块获取测试',
            'time'=>mktime(7, 0, 0, $month, $day, $year),
            'start'=>mktime(6, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=>$default_interval,
            'align'=>$default_align
        ]
    ]
);

$test->assert(
    '一个跨天块测试，块状时间:23:00 - 05:00',
    [
        [
            'dtstart' => [
                'h' =>23,
                'i'=>0
            ],
            'dtend'=>[
                'h' =>5,
                'i'=>0
            ],
            'interval'=>2400,
            'align'=>1200,
        ]
    ],
    [
        [
            'title' => '01:00 预约块获取测试',
            'time'=>mktime(1, 0, 0, $month, $day, $year),
            'start'=>$day_start,
            'end'=>mktime(5, 0, 0, $month, $day, $year),
            'interval'=>2400,
            'align'=>1200
        ],
        [
            'title' => '06:00 预约块获取测试',
            'time'=>mktime(6, 0, 0, $month, $day, $year),
            'start'=>mktime(5, 0, 0, $month, $day, $year),
            'end'=>mktime(23, 0, 0, $month, $day, $year),
            'interval'=> $default_interval,
            'align'=>$default_align
        ],
        [
            'title' => '23:00 预约块获取测试',
            'time'=>mktime(23, 0, 0, $month, $day, $year),
            'start'=>mktime(23, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=> 2400,
            'align'=>1200
        ],
        [
            'title' => '23:30 预约块获取测试',
            'time' => mktime(23, 30 ,0, $month, $day, $year),
            'start'=> mktime(23, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=>2400,
            'align'=>1200
        ]
    ]
);

$test->assert(
    '两个间隔的普通块测试, 块状时间: 02:00 - 05:00、08:00 - 14:00',
    [
        [
            'dtstart' =>[
                'h'=>2,
                'i'=>0
            ],
            'dtend'=>[
                'h' =>5,
                'i'=>0
            ],
            'interval'=>1200,
            'align'=>2400
        ],
        [
            'dtstart'=>[
                'h' =>8,
                'i'=>0
            ],
            'dtend'=>[
                'h' =>14,
                'i'=>0
            ],
            'interval'=>1800,
            'align'=>3600
        ]
    ],
    [
        [
            'title'=> '01:00 预约块获取测试',
            'time'=> mktime(1, 0, 0, $month, $day, $year),
            'start'=> $day_start,
            'end'=> mktime(2, 0, 0, $month, $day, $year),
            'interval'=>$default_interval,
            'align'=> $default_align
        ],
        [
            'title'=> '03:00 预约块获取测试',
            'time'=> mktime(3, 0, 0, $month, $day, $year),
            'start'=> mktime(2, 0, 0, $month, $day, $year),
            'end'=> mktime(5, 0, 0, $month, $day, $year),
            'interval'=> 1200,
            'align'=> 2400
        ],
        [
            'title' =>'07:00 预约块获取测试',
            'time'=> mktime(7, 0, 0, $month, $day, $year),
            'start'=> mktime(5, 0, 0, $month, $day, $year),
            'end'=>mktime(8, 0, 0, $month, $day, $year),
            'interval'=> $default_interval,
            'align'=> $default_align
        ],
        [
            'title' => '10:00 预约块获取测试',
            'time'=>mktime(10, 0, 0, $month, $day, $year),
            'start'=>mktime(8, 0, 0, $month, $day, $year),
            'end'=>mktime(14, 0, 0, $month, $day, $year),
            'interval'=> 1800,
            'align'=> 3600
        ],
        [
            'title' => '20:00 预约块获取测试',
            'time'=> mktime(20, 0, 0, $month, $day, $year),
            'start'=>mktime(14, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=>$default_interval,
            'align'=>$default_align
        ]
    ]
);

$test->assert(
    '两个普通块交错测试, 块状时间 02:00 - 08:00 、 06:00 - 10:00',
    [
        [
            'dtstart' =>[
                'h'=>2,
                'i'=>0
            ],
            'dtend'=>[
                'h' =>8,
                'i'=> 0
            ],
            'interval'=>1200,
            'align'=>2400
        ],
        [
            'dtstart' =>[
                'h'=>6,
                'i'=>0
            ],
            'dtend'=>[
                'h'=>10,
                'i'=>0
            ],
            'interval'=>3600,
            'align'=> 1800
        ]
    ],
    [
        [
            'title'=>'01:00 预约块获取测试',
            'time'=>mktime(1, 0, 0, $month, $day, $year),
            'start'=>$day_start,
            'end'=>mktime(2, 0, 0, $month, $day, $year),
            'interval'=>$default_interval,
            'align'=>$default_align
        ],
        [
            'title' => '03:00 预约块获取测试',
            'time'=>mktime(3, 0, 0, $month, $day, $year),
            'start'=>mktime(2, 0, 0, $month, $day, $year),
            'end'=>mktime(8, 0, 0, $month, $day, $year),
            'interval'=> 1200,
            'align'=> 2400
        ],
        [
            'title' => '07:00 预约块获取测试',
            'time'=>mktime(7, 0, 0, $month, $day, $year),
            'start'=>mktime(2, 0, 0, $month, $day, $year),
            'end'=>mktime(8, 0, 0, $month, $day, $year),
            'interval'=> 1200,
            'align'=> 2400
        ],
        [
            'title' => '09:00 预约块获取测试',
            'time'=>mktime(9, 0, 0, $month, $day, $year),
            'start'=>mktime(6, 0, 0, $month, $day, $year),
            'end'=>mktime(10, 0, 0, $month, $day, $year),
            'interval'=> 3600,
            'align'=> 1800
        ],
        [
            'title' => '12:00 预约块获取测试',
            'time'=>mktime(12, 0, 0, $month, $day, $year),
            'start'=>mktime(10, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=> $default_interval,
            'align'=> $default_align
        ],
    ]
);

$test->assert(
    '两个普通块相邻测试, 块状时间 02:00 - 08:00 、 08:00 - 10:00',
    [
        [
            'dtstart' =>[
                'h'=>2,
                'i'=>0
            ],
            'dtend'=>[
                'h' =>8,
                'i'=> 0
            ],
            'interval'=>1200,
            'align'=>2400
        ],
        [
            'dtstart' =>[
                'h'=>8,
                'i'=>0
            ],
            'dtend'=>[
                'h'=>10,
                'i'=>0
            ],
            'interval'=>3600,
            'align'=> 3600
        ]
    ],
    [
        [
            'title'=>'01:00 预约块获取测试',
            'time'=>mktime(1, 0, 0, $month, $day, $year),
            'start'=>$day_start,
            'end'=>mktime(2, 0, 0, $month, $day, $year),
            'interval'=>$default_interval,
            'align'=>$default_align
        ],
        [
            'title' => '02:00 预约块获取测试',
            'time'=>mktime(2, 0, 0, $month, $day, $year),
            'start'=>mktime(2, 0, 0, $month, $day, $year),
            'end'=>mktime(8, 0, 0, $month, $day, $year),
            'interval'=> 1200,
            'align'=> 2400
        ],
        [
            'title' => '08:00 预约块获取测试',
            'time'=>mktime(8, 0, 0, $month, $day, $year),
            'start'=>mktime(8, 0, 0, $month, $day, $year),
            'end'=>mktime(10, 0, 0, $month, $day, $year),
            'interval'=> 3600,
            'align'=> 3600
        ],
        [
            'title' => '10:00 预约块获取测试',
            'time'=>mktime(10, 0, 0, $month, $day, $year),
            'start'=>mktime(10, 0, 0, $month, $day, $year),
            'end'=> $day_end,
            'interval'=> $default_interval,
            'align'=> $default_align
        ],
        [
            'title' => '12:00 预约块获取测试',
            'time'=>mktime(12, 0, 0, $month, $day, $year),
            'start'=>mktime(10, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=> $default_interval,
            'align'=> $default_align
        ],
    ]
);

$test->assert(
    '两个跨天块交错测试, 块状时间: 22:00 - 02:00、 20:00 - 06:00',
    [
        [
            'dtstart' =>[
                'h'=>22,
                'i'=>0
            ],
            'dtend'=>[
                'h' =>2,
                'i'=> 0
            ],
            'interval'=>1200,
            'align'=>2400
        ],
        [
            'dtstart' =>[
                'h'=>20,
                'i'=>0
            ],
            'dtend'=>[
                'h'=>6,
                'i'=>0
            ],
            'interval'=>3600,
            'align'=> 3600
        ]
    ],
    [
        [
            'title'=>'10:00 预约块获取测试',
            'time'=>mktime(10, 0, 0, $month, $day, $year),
            'start'=>mktime(6, 0, 0, $month, $day, $year),
            'end'=>mktime(20, 0, 0, $month, $day, $year),
            'interval'=>$default_interval,
            'align'=>$default_align
        ],
        [
            'title' => '21:00 预约块获取测试',
            'time'=>mktime(21, 0, 0, $month, $day, $year),
            'start'=>mktime(20, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=> 3600,
            'align'=> 3600
        ],
        [
            'title' => '22:00 预约块获取测试',
            'time'=>mktime(22, 0, 0, $month, $day, $year),
            'start'=>mktime(22, 0, 0, $month, $day, $year),
            'end'=> $day_end,
            'interval'=> 1200,
            'align'=> 2400
        ],
        [
            'title' => '23:00 预约块获取测试',
            'time'=>mktime(23, 0, 0, $month, $day, $year),
            'start'=>mktime(22, 0, 0, $month, $day, $year),
            'end'=> $day_end,
            'interval'=> 1200,
            'align'=> 2400
        ],
        [
            'title' => '01:00 预约块获取测试',
            'time'=>mktime(1, 0, 0, $month, $day, $year),
            'start'=>$day_start,
            'end'=>mktime(2, 0, 0, $month, $day, $year),
            'interval'=> 1200,
            'align'=> 2400
        ],
        [
            'title' => '04:00 预约块获取测试',
            'time'=>mktime(4, 0, 0, $month, $day, $year),
            'start'=>$day_start,
            'end'=>mktime(6, 0, 0, $month, $day, $year),
            'interval'=> 3600,
            'align'=> 3600
        ],
    ]
);

$test->assert(
    '一个普通块，一个跨天块， 不交错测试， 块状时间: 22:00 - 04:00、06:00 - 12:00',
    [
        [
            'dtstart' => [
                'h' =>22,
                'i'=>0
            ],
            'dtend'=>[
                'h' =>4,
                'i'=>0
            ],
            'interval'=>1800,
            'align'=>1800,
        ],
        [
            'dtstart' =>[
                'h'=>6,
                'i'=>0
            ],
            'dtend'=>[
                'h'=>12,
                'i'=>0
            ],
            'interval'=>1200,
            'align'=>2400
        ]
    ],
    [
        [
            'title' => '23:00 预约块获取测试',
            'time'=>mktime(23, 0, 0, $month, $day, $year),
            'start'=>mktime(22, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=> 1800,
            'align'=>1800
        ],
        [
            'title' =>'02:00 预约块获取测试',
            'time'=>mktime(2, 0, 0, $month, $day, $year),
            'start'=>$day_start,
            'end'=>mktime(4, 0, 0, $month, $day, $year),
            'interval'=> 1800,
            'align'=>1800
        ],
        [
            'title' =>'05:00 预约块获取测试',
            'time'=>mktime(5, 0, 0, $month, $day, $year),
            'start'=>mktime(4, 0, 0, $month, $day, $year),
            'end'=>mktime(6, 0 ,0, $month, $day, $year),
            'interval'=>$default_interval,
            'align'=>$default_align
        ],
        [
            'title'=>'08:00 预约块获取测试',
            'time'=>mktime(8, 0, 0, $month, $day, $year),
            'start'=>mktime(6, 0, 0, $month, $day, $year),
            'end'=>mktime(12, 0, 0, $month, $day, $year),
            'interval'=>1200,
            'align'=>2400
        ],
        [
            'title'=>'16:00 预约块获取测试',
            'time'=>mktime(16, 0, 0, $month, $day, $year),
            'start'=>mktime(12,0, 0, $month, $day, $year),
            'end'=>mktime(22, 0, 0, $month, $day, $year),
            'interval'=> $default_interval,
            'align'=>$default_align
        ]
    ]
);

$test->assert(
    '综合测试，一个普通块，两个相邻的普通块, 其中一个块和另外一个普通快交错， 两个跨天交错块，跨天交错块和其他块不交错，块状时间: 20:00 - 04:00、22:00 - 02:00、 06:00 - 08:00、07:00 - 10:00 、 10:00 - 14:00',
    [
        [
            'dtstart' => [
                'h' =>20,
                'i'=>0
            ],
            'dtend'=>[
                'h' =>4,
                'i'=>0
            ],
            'interval'=>1800,
            'align'=>1800,
        ],
        [
            'dtstart' =>[
                'h'=>22,
                'i'=>0
            ],
            'dtend'=>[
                'h'=>2,
                'i'=>0
            ],
            'interval'=>1200,
            'align'=>2400
        ],
        [
            'dtstart' =>[
                'h'=>6,
                'i'=>0
            ],
            'dtend'=>[
                'h'=>8,
                'i'=>0
            ],
            'interval'=>1200,
            'align'=> 1200
        ],
        [
            'dtstart' =>[
                'h'=>7,
                'i'=>0
            ],
            'dtend'=>[
                'h'=>10,
                'i'=>0
            ],
            'interval'=> 3600,
            'align'=> 3600
        ],
        [
            'dtstart' =>[
                'h'=>10,
                'i'=>0
            ],
            'dtend'=>[
                'h'=>14,
                'i'=>0
            ],
            'interval'=> 1800,
            'align'=>2400
        ],

    ],
    [
        [
            'title' => '00:00 预约块获取测试',
            'time'=>$day_start,
            'start'=>$day_start,
            'end'=>mktime(4, 0, 0, $month, $day, $year),
            'interval'=> 1800,
            'align'=>1800
        ],
        [
            'title' => '01:00 预约块获取测试',
            'time'=>mktime(1, 0, 0, $month, $day, $year),
            'start'=>$day_start,
            'end'=>mktime(4, 0, 0, $month, $day, $year),
            'interval'=> 1800,
            'align'=>1800
        ],
        [
            'title' =>'02:00 预约块获取测试',
            'time'=>mktime(2, 0, 0, $month, $day, $year),
            'start'=>$day_start,
            'end'=>mktime(4, 0, 0, $month, $day, $year),
            'interval'=> 1800,
            'align'=>1800
        ],
        [
            'title' =>'03:00 预约块获取测试',
            'time'=>mktime(3, 0, 0, $month, $day, $year),
            'start'=>$day_start,
            'end'=>mktime(4, 0, 0, $month, $day, $year),
            'interval'=> 1800,
            'align'=>1800
        ],
        [
            'title' =>'04:00 预约块获取测试',
            'time'=>mktime(4, 0, 0, $month, $day, $year),
            'start'=>mktime(4, 0, 0, $month, $day, $year),
            'end'=>mktime(6, 0, 0, $month, $day, $year),
            'interval'=> $default_interval,
            'align'=> $default_align
        ],
        [
            'title' =>'05:00 预约块获取测试',
            'time'=>mktime(5, 0, 0, $month, $day, $year),
            'start'=>mktime(4, 0, 0, $month, $day, $year),
            'end'=>mktime(6, 0 ,0, $month, $day, $year),
            'interval'=>$default_interval,
            'align'=>$default_align
        ],
        [
            'title' =>'06:00 预约块获取测试',
            'time'=>mktime(6, 0, 0, $month, $day, $year),
            'start'=>mktime(6, 0, 0, $month, $day, $year),
            'end'=>mktime(8, 0 ,0, $month, $day, $year),
            'interval'=> 1200,
            'align'=> 1200
        ],
        [
            'title' =>'07:00 预约块获取测试',
            'time'=>mktime(7, 0, 0, $month, $day, $year),
            'start'=>mktime(6, 0, 0, $month, $day, $year),
            'end'=>mktime(8, 0 ,0, $month, $day, $year),
            'interval'=> 1200,
            'align'=> 1200
        ],
        [
            'title'=>'08:00 预约块获取测试',
            'time'=>mktime(8, 0, 0, $month, $day, $year),
            'start'=>mktime(7, 0, 0, $month, $day, $year),
            'end'=>mktime(10, 0, 0, $month, $day, $year),
            'interval'=> 3600,
            'align'=> 3600
        ],
        [
            'title'=>'09:00 预约块获取测试',
            'time'=>mktime(9, 0, 0, $month, $day, $year),
            'start'=>mktime(7, 0, 0, $month, $day, $year),
            'end'=>mktime(10, 0, 0, $month, $day, $year),
            'interval'=> 3600,
            'align'=> 3600
        ],
        [
            'title'=>'10:00 预约块获取测试',
            'time'=>mktime(10, 0, 0, $month, $day, $year),
            'start'=>mktime(10, 0, 0, $month, $day, $year),
            'end'=>mktime(14, 0, 0, $month, $day, $year),
            'interval'=> 1800,
            'align'=>2400
        ],
        [
            'title'=>'12:00 预约块获取测试',
            'time'=>mktime(12, 0, 0, $month, $day, $year),
            'start'=>mktime(10,0, 0, $month, $day, $year),
            'end'=>mktime(14, 0, 0, $month, $day, $year),
            'interval'=> 1800,
            'align'=> 2400
        ],
        [
            'title'=>'14:00 预约块获取测试',
            'time'=>mktime(14, 0, 0, $month, $day, $year),
            'start'=>mktime(14, 0, 0, $month, $day, $year),
            'end'=>mktime(20, 0, 0, $month, $day, $year),
            'interval'=> $default_interval,
            'align'=> $default_align
        ],
        [
            'title'=>'16:00 预约块获取测试',
            'time'=>mktime(16, 0, 0, $month, $day, $year),
            'start'=>mktime(14, 0, 0, $month, $day, $year),
            'end'=>mktime(20, 0, 0, $month, $day, $year),
            'interval'=> $default_interval,
            'align'=> $default_align
        ],
        [
            'title'=>'20:00 预约块获取测试',
            'time'=>mktime(20, 0, 0, $month, $day, $year),
            'start'=>mktime(20, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=> 1800,
            'align'=> 1800
        ],
        [
            'title'=>'21:00 预约块获取测试',
            'time'=>mktime(21, 0, 0, $month, $day, $year),
            'start'=>mktime(20, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=> 1800,
            'align'=> 1800
        ],
        [
            'title'=>'22:00 预约块获取测试',
            'time'=>mktime(22, 0, 0, $month, $day, $year),
            'start'=>mktime(20, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=> 1800,
            'align'=> 1800
        ],
        [
            'title'=>'23:00 预约块获取测试',
            'time'=>mktime(23, 0, 0, $month, $day, $year),
            'start'=>mktime(20, 0, 0, $month, $day, $year),
            'end'=>$day_end,
            'interval'=> 1800,
            'align'=> 1800
        ],
    ]
);

$test->tear_down();
