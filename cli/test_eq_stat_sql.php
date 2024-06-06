#!/usr/bin/env php

<?php

/*
 * @file   test_eq_stat_sql.php
 * @author RUI MA<rui.ma@geneegroup.com> Xiaopei.Li<xiaopei.li@geneegroup.com>
 * @date   2011-11-29
 *
 * @brief  测试eq_stat中SQL语句是否正确
 * @usage  SITE_ID=cf LAB_ID=empty ./test_eq_stat_sql.php
 */

require 'base.php';

$now = time();
$default_password = '123456';
$default_backend = 'database';

function secho($message) {
    //success echo
    Upgrader::echo_success($message);
}

function fecho($message) {
    //fail echo
    Upgrader::echo_fail($message);
}

function hecho($message) {
    //hight echo
    Upgrader::echo_title($message);
}

$tags = [
//标签
    'group'=>[
        [
            'name'=>'化学学院',
        ],
        [
            'name'=>'物理学院',
            'subtag'=>[
                '光电材料研发中心',
                '机电与冶炼中心'
            ]
        ],
        [
            'name'=>'医学院',
        ],
        [
            'name'=>'药学院',
        ],
        [
            'name'=>'生命科学学院',
        ]
    ],
    'equipment'=>[
        [
            'name'=>'电子光学仪器',
            'subtag'=>[
                '电光仪器A型',
                '电光仪器B型'
            ]
        ],
        [
            'name'=>'质谱仪器',
            'subtag'=>[
                '质谱仪器I型',
                '质谱仪器II型'
            ]
        ],
        [
            'name'=>'光谱仪器',
            'subtag'=>[
                '光谱仪器甲型',
                '光谱仪器乙型'
            ]
        ],
        [
            'name'=>'热分析仪器',
        ],
        [
            'name'=>'长度计量仪器',
        ]
    ]
];

$users = [
//用户
    [
        'name'=>'柴志华',
        'group'=>'生命科学学院',
        'token'=>'czh',
    ],
    [
        'name'=>'程莹',
        'group'=>'化学学院',
        'token'=>'cy',
    ],
    [
        'name'=>'胡宁',
        'group'=>'光电材料研发中心',
        'token'=>'hn'
    ],
    [
        'name'=>'沈冰',
        'group'=>'机电与冶炼中心',
        'token'=>'sb'
    ],
    [
        'name'=>'陈建宁',
        'group'=>'医学院',
        'token'=>'cjn'
    ],
    [
        'name'=>'许宏山',
        'group'=>'药学院',
        'token'=>'xhs'
    ],
    [
        'name'=>'user1',
        'group'=>'组织机构',
        'token'=>'user1'
    ]
]; 

$equipments = [
//仪器
    [
        'name'=>'核磁共振波谱仪',
        'price'=>10000,
        'group_tag'=>'药学院',
        'equipment_tag'=>'光谱仪器甲型',
        'month'=>'1'
    ],
    [
        'name'=>'扫描式电子显微镜',
        'price'=>10000,
        'group_tag'=>'生命科学学院',
        'equipment_tag'=>'热分析仪器',
        'month'=>'2'
    ],
    [
        'name'=>'液相色谱仪',
        'price'=>7000,
        'group_tag'=>'医学院',
        'equipment_tag'=>'质谱仪器II型',
        'month'=>'4'
    ],
    [
        'name'=>'300M核磁',
        'price'=>6000,
        'group_tag'=>'化学学院',
        'equipment_tag'=>'电光仪器B型',
        'month'=>'4'
    ],
    [
        'name'=>'单晶衍射仪',
        'price'=>15000,
        'group_tag'=>'机电与冶炼中心',
        'equipment_tag'=>'长度计量仪器',
        'month'=>'4'
    ],
    [
        'name'=>'X射线衍射仪',
        'price'=>15000,
        'group_tag'=>'光电材料研发中心',
        'equipment_tag'=>'长度计量仪器',
        'month'=>'4'
    ],
    [
        'name'=>'原子力显微镜',
        'price'=>9000,
        'group_tag'=>'化学学院',
        'equipment_tag'=>'热分析仪器',
        'month'=>'4'
    ],
    [
        'name'=>'综合吸附仪',
        'price'=>9000,
        'group_tag'=>'生命科学学院',
        'equipment_tag'=>'电光仪器A型',
        'month'=>'4'
    ],
    [
        'name'=>'多功能电子能谱',
        'price'=>9000,
        'group_tag'=>'医学院',
        'equipment_tag'=>'质谱仪器I型',
        'month'=>'4'
    ],
    [
        'name'=>'流式细胞仪',
        'price'=>9000,
        'group_tag'=>'药学院',
        'equipment_tag'=>'长度计量仪器',
        'month'=>'4'
    ],
    [
        'name'=>'超临界流体色谱仪',
        'price'=>80000,
        'month'=>'7'
    ],
];

$ge_trainings = [
//团体培训
    [
        'equipment_name'=>'核磁共振波谱仪',
        'ntotal'=>10,
        'napproved'=>5
    ],
    [
        'equipment_name'=>'扫描式电子显微镜',
        'ntotal'=>10,
        'napproved'=>5
    ],
    [
        'equipment_name'=>'原子力显微镜',
        'ntotal'=>10,
        'napproved'=>5
    ],
    [
        'equipment_name'=>'综合吸附仪',
        'ntotal'=>10,
        'napproved'=>6
    ],
    [
        'equipment_name'=>'多功能电子能谱',
        'ntotal'=>10,
        'napproved'=>9
    ],
    [
        'equipment_name'=>'X射线衍射仪',
        'ntotal'=>10,
        'napproved'=>8
    ],
    [
        'equipment_name'=>'单晶衍射仪',
        'ntotal'=>10,
        'napproved'=>7
    ],
    [
        'equipment_name'=>'超临界流体色谱仪',
        'ntotal'=>10,
        'napproved'=>1
    ]
];

$ue_trainings = [
//个人培训
    [
        'equipment_name'=>'扫描式电子显微镜',
        'user_name'=>'柴志华'
    ],
    [
        'equipment_name'=>'综合吸附仪',
        'user_name'=>'柴志华'
    ],
    [
        'equipment_name'=>'原子力显微镜',
        'user_name'=>'程莹'
    ],
    [
        'equipment_name'=>'300M核磁',
        'user_name'=>'程莹'
    ],
    [
        'equipment_name'=>'X射线衍射仪',
        'user_name'=>'胡宁'
    ],
    [
        'equipment_name'=>'单晶衍射仪',
        'user_name'=>'沈冰'
    ],
    [
        'equipment_name'=>'多功能电子能谱',
        'user_name'=>'陈建宁'
    ],
    [
        'equipment_name'=>'液相色谱仪',
        'user_name'=>'陈建宁'
    ],
    [
        'equipment_name'=>'流式细胞仪',
        'user_name'=>'许宏山'
    ],
    [
        'equipment_name'=>'核磁共振波谱仪',
        'user_name'=>'许宏山'
    ]
];

$projects = [
//项目
    [
        'name'=>'lims开发',
        'type'=>'RESEARCH'
    ],
    [
        'name'=>'cf开发',
        'type'=>'RESEARCH'
    ],
    [
        'name'=>'教师培训',
        'type'=>'SERVICE'
    ]
];

$records = [
//使用记录

    [
        'equipment_name'=>'扫描式电子显微镜',
        'samples'=>5,
        'user_name'=>'柴志华',
        'record_time'=>2
    ],
    [
        'equipment_name'=>'扫描式电子显微镜',
        'samples'=>1,
        'user_name'=>'超级用户',
        'record_time'=>1
    ],
    [
        'equipment_name'=>'扫描式电子显微镜',
        'samples'=>6,
        'user_name'=>'程莹',
        'record_time'=>3
    ],
    [
        'equipment_name'=>'核磁共振波谱仪',
        'samples'=>5,
        'user_name'=>'沈冰',
        'record_time'=>1
    ],
    [
        'equipment_name'=>'核磁共振波谱仪',
        'samples'=>5,
        'user_name'=>'超级用户',
        'record_time'=>2
    ],
    [
        'equipment_name'=>'原子力显微镜',
        'samples'=>1,
        'user_name'=>'超级用户',
        'record_time'=>3,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'综合吸附仪',
        'samples'=>1,
        'user_name'=>'超级用户',
        'record_time'=>1,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'多功能电子能谱',
        'samples'=>1,
        'user_name'=>'超级用户',
        'record_time'=>1,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'X射线衍射仪',
        'samples'=>1,
        'user_name'=>'超级用户',
        'record_time'=>1,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'单晶衍射仪',
        'samples'=>1,
        'user_name'=>'超级用户',
        'record_time'=>1,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'300M核磁',
        'samples'=>1,
        'user_name'=>'超级用户',
        'record_time'=>1,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'液相色谱仪',
        'samples'=>1,
        'user_name'=>'超级用户',
        'record_time'=>1,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'流式细胞仪',
        'samples'=>1,
        'user_name'=>'超级用户',
        'record_time'=>1,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'流式细胞仪',
        'samples'=>1,
        'user_name'=>'许宏山',
        'record_time'=>2,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'核磁共振波谱仪',
        'samples'=>1,
        'user_name'=>'许宏山',
        'record_time'=>3,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'X射线衍射仪',
        'samples'=>1,
        'user_name'=>'胡宁',
        'record_time'=>2,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'多功能电子能谱',
        'samples'=>1,
        'user_name'=>'陈建宁',
        'record_time'=>3,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'液相色谱仪',
        'samples'=>1,
        'user_name'=>'陈建宁',
        'record_time'=>2,
        'is_reserv'=>TRUE
    ],
    [
        'equipment_name'=>'超临界流体色谱仪',
        'samples'=>15,
        'user_name'=>'超级用户',
        'record_time'=>2
    ],
    [
        'equipment_name'=>'超临界流体色谱仪',
        'samples'=>15,
        'user_name'=>'user1',//测试用例中写使用非管理员用户
        'record_time'=>2
    ],
    [
        'equipment_name'=>'超临界流体色谱仪',
        'samples'=>15,
        'user_name'=>'user1',
        'record_time'=>5,
        'is_reserv'=>TRUE
    ]
];

$publications = [
//论文

    [
        'name'=>'论文1',
        'project'=>'lims开发',
        'equipments'=>[
            '核磁共振波谱仪',
            '扫描式电子显微镜' 
        ]
    ],
    [
        'name'=>'论文2',
        'project'=>'lims开发',
        'equipments'=>[
            '流式细胞仪',
            '扫描式电子显微镜' 
        ]
    ],
    [
        'name'=>'论文3',
        'project'=>'lims开发',
        'equipments'=>[
            '核磁共振波谱仪',
            '原子力显微镜' 
        ]
    ],
    [
        'name'=>'论文4',
        'project'=>'lims开发',
        'equipments'=>[
            '综合吸附仪',
            '原子力显微镜' 
        ]
    ],
    [
        'name'=>'论文5',
        'project'=>'lims开发',
        'equipments'=>[
            '综合吸附仪',
            '多功能电子能谱' 
        ]
    ],
    [
        'name'=>'论文6',
        'project'=>'lims开发',
        'equipments'=>[
            'X射线衍射仪',
            '多功能电子能谱' 
        ]
    ],
    [
        'name'=>'论文7',
        'project'=>'lims开发',
        'equipments'=>[
            'X射线衍射仪',
            '单晶衍射仪' 
        ]
    ],
    [
        'name'=>'论文8',
        'project'=>'lims开发',
        'equipments'=>[
            '300M核磁',
            '单晶衍射仪' 
        ]
    ],
    [
        'name'=>'论文9',
        'project'=>'lims开发',
        'equipments'=>[
            '300M核磁',
            '液相色谱仪' 
        ]
    ],
    [
        'name'=>'论文10',
        'project'=>'lims开发',
        'equipments'=>[
            '流式细胞仪',
            '液相色谱仪' 
        ]
    ],
    [
        'name'=>'论文11',
        'project'=>'lims开发',
        'equipments'=>[
            '单晶衍射仪',
            'X射线衍射仪' 
        ]
    ],
    [
        'name'=>'论文12',
        'project'=>'lims开发',
        'equipments'=>[
            '核磁共振波谱仪',
            '扫描式电子显微镜' 
        ]
    ],
    [
        'name'=>'论文13',
        'project'=>'lims开发',
        'equipments'=>[
            '超临界流体色谱仪',
        ]
    ],
];

$equipment_count = [
//仪器台数统计    
    [
        'tag_name'=>'热分析仪器',
        'result'=>2
    ],
    [
        'tag_name'=>'光谱仪器',
        'result'=>1
    ],
    [
        'tag_name'=>'质谱仪器',
        'result'=>2
    ],
    [
        'tag_name'=>'电子光学仪器',
        'result'=>2
    ],
    [
        'tag_name'=>'长度计量仪器',
        'result'=>3
    ],
    [
        'tag_name'=>'仪器分类',//测试用例和swf图形页面显示其他
        'result'=>1
    ],
    [
        'tag_name'=>'组织机构',
        'result'=>1
    ],
    [
        'tag_name'=>'生命科学学院',
        'result'=>2
    ],
    [
        'tag_name'=>'药学院',
        'result'=>2
    ],
    [
        'tag_name'=>'医学院',
        'result'=>2
    ],
    [
        'tag_name'=>'物理学院',
        'result'=>2
    ],
    [
        'tag_name'=>'化学学院',
        'result'=>2
    ]
];

$equipment_value = [
//仪器总值
    [
        'tag_name'=>'热分析仪器',
        'result'=>19000
    ],
    [
        'tag_name'=>'光谱仪器',
        'result'=>10000
    ],
    [
        'tag_name'=>'质谱仪器',
        'result'=>16000
    ],
    [
        'tag_name'=>'电子光学仪器',
        'result'=>15000
    ],
    [
        'tag_name'=>'长度计量仪器',
        'result'=>39000
    ],
    [
        'tag_name'=>'仪器分类',//swf中显示其他
        'result'=>80000
    ],
    [
        'tag_name'=>'化学学院',
        'result'=>15000
    ],
    [
        'tag_name'=>'物理学院',
        'result'=>30000
    ],
    [
        'tag_name'=>'医学院',
        'result'=>16000
    ],
    [
        'tag_name'=>'药学院',
        'result'=>19000
    ],
    [
        'tag_name'=>'生命科学学院',
        'result'=>19000
    ],
    [
        'tag_name'=>'组织机构',//swf中显示其他
        'result'=>80000
    ]
];

$total_trainees = [
//培训人数
    [
        'tag_name'=>'热分析仪器',
        'result'=>12
    ],
    [
        'tag_name'=>'光谱仪器',
        'result'=>6
    ],
    [
        'tag_name'=>'质谱仪器',
        'result'=>11
    ],
    [
        'tag_name'=>'电子光学仪器',
        'result'=>8
    ],
    [
        'tag_name'=>'长度计量仪器',
        'result'=>18
    ],
    [
        'tag_name'=>'仪器分类',//swf中显示其他 
        'result'=>1
    ],
    [
        'tag_name'=>'医学院',
        'result'=>11
    ],
    [
        'tag_name'=>'物理学院',
        'result'=>17
    ],
    [
        'tag_name'=>'化学学院',
        'result'=>7
    ],
    [
        'tag_name'=>'生命科学学院',
        'result'=>13
    ],
    [
        'tag_name'=>'药学院',
        'result'=>7
    ],
    [
        'tag_name'=>'组织机构',//swf中显示其他
        'result'=>1
    ],
];

$pubs = [
//论文
    [
        'tag_name'=>'仪器分类',
        'result'=>1
    ],
    [
        'tag_name'=>'热分析仪器',
        'result'=>5
    ],
    [
        'tag_name'=>'光谱仪器',
        'result'=>3
    ],
    [
        'tag_name'=>'质谱仪器',
        'result'=>4
    ],
    [
        'tag_name'=>'电子光学仪器',
        'result'=>4
    ],
    [
        'tag_name'=>'长度计量仪器',
        'result'=>6
    ],
    [
        'tag_name'=>'组织机构',
        'result'=>1
    ],
    [
        'tag_name'=>'医学院',
        'result'=>4
    ],
    [
        'tag_name'=>'物理学院',
        'result'=>4
    ],
    [
        'tag_name'=>'化学学院',
        'result'=>4
    ],
    [
        'tag_name'=>'生命科学学院',
        'result'=>5
    ],
    [
        'tag_name'=>'药学院',
        'result'=>5
    ]
];

$use_time = [
//使用次数
    [
        'tag_name'=>'仪器分类',
        'result'=>3
    ],
    [
        'tag_name'=>'热分析仪器',
        'result'=>4
    ],
    [
        'tag_name'=>'光谱仪器',
        'result'=>3
    ],
    [
        'tag_name'=>'质谱仪器',
        'result'=>4
    ],
    [
        'tag_name'=>'电子光学仪器',
        'result'=>2
    ],
    [
        'tag_name'=>'长度计量仪器',
        'result'=>5
    ],
    [
        'tag_name'=>'组织机构',
        'result'=>3
    ],
    [
        'tag_name'=>'医学院',
        'result'=>4
    ],
        [
        'tag_name'=>'物理学院',
        'result'=>3
    ],
    [
        'tag_name'=>'化学学院',
        'result'=>2
    ],
    [
        'tag_name'=>'生命科学学院',
        'result'=>4
    ],
    [
        'tag_name'=>'药学院',
        'result'=>5
    ]
];


$record_sample = [
//送样数测试
    [
        'tag_name'=>'热分析仪器',
        'result'=>13
    ],
    [
        'tag_name'=>'长度计量仪器',
        'result'=>5
    ],
    [
        'tag_name'=>'质谱仪器',
        'result'=>4
    ],
    [
        'tag_name'=>'电子光学仪器',
        'result'=>2
    ],
    [
        'tag_name'=>'光谱仪器',
        'result'=>11
    ],
    [
        'tag_name'=>'仪器分类',//swf中显示其他
        'result'=>31
    ],
    [
        'tag_name'=>'组织机构',//swf中显示其他
        'result'=>31
    ],
    [
        'tag_name'=>'医学院',
        'result'=>4
    ],
    [
        'tag_name'=>'物理学院',
        'result'=>3
    ],
    [
        'tag_name'=>'化学学院',
        'result'=>2
    ],
    [
        'tag_name'=>'生命科学学院',
        'result'=>13
    ],
    [
        'tag_name'=>'药学院',
        'result'=>13
    ],
];


$time_total = [
//使用机时
    [
        'tag_name'=>'仪器分类',//swf中显示其他
        'result'=>9
    ],
    [
        'tag_name'=>'质谱仪器', 
        'result'=>7
    ],
    [
        'tag_name'=>'光谱仪器', 
        'result'=>6
    ],
    [
        'tag_name'=>'热分析仪器', 
        'result'=>9
    ],
    [
        'tag_name'=>'长度计量仪器', 
        'result'=>7
    ],
    [
        'tag_name'=>'电子光学仪器',
        'result'=>2
    ],
    [
        'tag_name'=>'组织机构',//swf中显示其他
        'result'=>9
    ],
    [
        'tag_name'=>'化学学院', 
        'result'=>4
    ],
    [
        'tag_name'=>'物理学院', 
        'result'=>4
    ],
    [
        'tag_name'=>'医学院', 
        'result'=>7
    ],
    [
        'tag_name'=>'药学院', 
        'result'=>9
    ],
    [
        'tag_name'=>'生命科学学院', 
        'result'=>7
    ]
];

$time_open = [
//开放机时长
    [
        'tag_name'=>'仪器分类',//swf中显示其他
        'result'=>7
    ],
    [
        'tag_name'=>'质谱仪器',
        'result'=>5
    ],
    [
        'tag_name'=>'光谱仪器',
        'result'=>4
    ],
    [
        'tag_name'=>'热分析仪器',
        'result'=>5
    ],
    [
        'tag_name'=>'长度计量仪器',
        'result'=>4
    ],
    [
        'tag_name'=>'组织机构',//swf中显示其他
        'result'=>7
    ],
    [
        'tag_name'=>'物理学院',
        'result'=>2
    ],
    [
        'tag_name'=>'医学院',
        'result'=>5
    ],
    [
        'tag_name'=>'药学院',
        'result'=>6
    ],
    [
        'tag_name'=>'生命科学学院',
        'result'=>5
    ]
];

$roles = [
//角色设定
    [
        'name'=>'超级管理员',
        'perms'=>[
            '管理所有内容'
        ],
        'users'=>[
            '超级用户'
        ]
    ],
    [
        'name'=>'查看者',
        'perms'=>[
            '查看统计图表'
        ],
        'users'=>[
            '程莹'
        ]
    ],

];

$db = Database::factory();

//测试之前数据库备份

$dbfile = LAB_PATH. T("private/backup/%lab_id.sql", ['%lab_id'=>LAB_ID]);
File::check_path($dbfile);
$db->snapshot($dbfile);

$db->empty_database();

Database::reset();

$super_user = O('user', ['name'=>'超级用户']);
//超级管理员，主要用于对测试中必填项目的填充处理

if (!$super_user->id) {
    $super_user = O('user');
    $super_user->name = '超级用户';
    $super_user->email = 'support@geneegroup.com';
    $super_user->atime = $now;

    if($super_user->save()) {
        secho(T("临时用户%super_user_name创建成功\n", ['%super_user_name'=>$super_user->name])); 

        $super_user_token = Auth::make_token('genee', $default_backend);
        $super_user->token = $super_user_token;
        $auth = new Auth($super_user_token);

        if ($auth->create($default_password) && $super_user->save()) {
            secho(T("临时用户%super_user_name帐号创建成功\n", ['%super_user_name'=>$super_user->name])); 
        }
        else {
            fecho(T("临时用户%super_user_name帐号创建失败\n", ['%super_user_name'=>$super_user->name])); 
        }
    }
    else {
        fecho(T("临时用户%super_user_name创建失败\n", ['%super_user_name'=>$super_user->name])); 
    }
}

$super_lab = O('lab', ['name'=>'测试实验室']);
//超级实验室，主要用于对测试中必填羡慕的填充处理

if (!$super_lab->id) {
    $super_lab = O('lab');
    $super_lab->name = '测试实验室';
    $super_lab->atime = $now;
    if($super_lab->save()) {
        secho(T("临时实验室%super_lab_name创建成功\n", ['%super_lab_name'=>$super_lab->name])); 
    }
    else {
        fecho(T("临时实验室%super_lab_name创建失败\n", ['%super_lab_name'=>$super_lab->name])); 
    }
}

$super_user->save();
$super_user->connect($super_lab);

//生成标签
$group_root_tag = Tag_Model::root('group');

if (!$group_root_tag->id) {
    $group_root_tag = O('tag');
    $group_root_tag->name = '组织机构';
    $group_root_tag->ctime = $now;
    $group_root_tag->mtime = $now;
    $group_root_tag->parent = NULL;
    $group_root_tag->root = NULL;
    $group_root_tag->readonly = TRUE;

    if($group_root_tag->save()) {
        secho(T("标签 %tag_name 添加成功\n", ['%tag_name'=>$group_root_tag->name]));
    }
    else {
        fecho(T("标签 %tag_name 添加失败\n", ['%tag_name'=>$group_root_tag->name]));
    }
}

$equipment_root_tag = Tag_Model::root('equipment');

if (!$equipment_root_tag->id) {
    $equipment_root_tag = O('tag');
    $equipment_root_tag->name = '仪器分类';
    $equipment_root_tag->ctime = $now;
    $equipment_root_tag->mtime = $now;
    $equipment_root_tag->parent = NULL;
    $equipment_root_tag->root = NULL;
    $equipment_root_tag->readonly = TRUE;

    if($equipment_root_tag->save()) {
        secho(T("标签 %tag_name 添加成功\n", ['%tag_name'=>$equipment_root_tag->name]));
    }
    else {
        fecho(T("标签 %tag_name 添加失败\n", ['%tag_name'=>$equipment_root_tag->name]));
    }
}


foreach($tags as $tag_type=>$tag_value) {

    switch($tag_type) {
    case 'group':
        foreach($tag_value as $t) {
            $new_tag = O('tag'); 
            $new_tag->name = $t['name'];
            $new_tag->root = $group_root_tag;
            $new_tag->parent = $group_root_tag;

            if($new_tag->save()) {
                secho(T("标签%tag_name创建成功\n", ['%tag_name'=>$new_tag->name])); 
            }
            else {
                fecho(T("标签%tag_name创建失败\n", ['%tag_name'=>$t['name']])); 
            }

            if ($t['subtag']) {
                foreach($t['subtag'] as $st) {
                    $sub_tag = O('tag'); 
                    $sub_tag->name = $st;
                    $sub_tag->parent = $new_tag;
                    $sub_tag->root = $group_root_tag;
                    if($sub_tag->save()) {
                        secho(T("子标签%sub_tag_name创建成功\n", ['%sub_tag_name'=>$sub_tag_name])); 
                    }
                    else {
                        fecho(T("子标签%sub_tag_name创建失败\n", ['%sub_tag_name'=>$sub_tag_name])); 
                    }
                }
            }
        }
        break;

    case 'equipment':
        foreach($tag_value as $t) {
            $new_tag = O('tag'); 
            $new_tag->name = $t['name'];
            $new_tag->root = $equipment_root_tag;
            $new_tag->parent = $equipment_root_tag;
            if($new_tag->save()) {
                secho(T("标签%tag_name创建成功\n", ['%tag_name'=>$new_tag->name])); 
            };

            if ($t['subtag']) {
                foreach($t['subtag'] as $st) {
                    $sub_tag = O('tag'); 
                    $sub_tag->name = $st;
                    $sub_tag->parent = $new_tag;
                    $sub_tag->root = $equipment_root_tag;
                    if($sub_tag->save()) {
                        secho(T("子标签%sub_tag_name创建成功\n", ['%sub_tag_name'=>$sub_tag->name])); 
                    }
                }
            }
        }
        break;
    }
}

//生成用户
foreach($users as $u) {

    $user = O('user');
    $user->name = $u['name'];
    $user->group = O('tag', ['name'=>$u['group']]);
    $user->email = $u['name']. '@geneegroup.com';
    $user->atime = $now;
    $u_token = Auth::make_token($u['token'], $default_backend);
    $user->token = $u_token;
    $u_auth = new Auth($u_token); 

    if($user->save()) {
        $user->connect($super_lab);
        secho(T("新用户%name创建成功\n", ['%name'=>$u['name']]));
        if ($u_auth->create($default_password)) {
            secho(T("新用户%name帐号创建成功\n", ['%name'=>$u['name']]));
        }
        else {
            fecho(T("新用户%name帐号创建失败\n", ['%name'=>$u['name']])); 
        }
    }
    else {
        fecho(T("新用户%name创建失败\n", ['%name'=>$u['name']])); 
    }
}

foreach($roles as $r) {

    $role = O('role');
    $role->name = $r['name'];
    if($role->save()) {
        secho(T("角色%role_name创建成功", ['%role_name'=>$r['name']]));
        //设定权限 
        $role_perms = [];

        foreach($r['perms'] as $p) {
            $role_perms[$p] = 'on';
        }

        Properties::factory($role)->set('perms', $role_perms)->save();

        foreach($r['users'] as $u) {
            $user = O('user', ['name'=>$u]); 
            $add_roles = [$role->id];
            $user->connect(['role', $add_roles]);//给用户设置角色
        }
    }
    else {
        fecho(T("角色%role_name创建失败", ['%role_name'=>$r['name']]));
    }
}

//生成仪器 (包括tag关联)
foreach($equipments as $e) {

    $new_equipment = O('equipment');
    $new_equipment->name = $e['name'];
    $new_equipment->price = $e['price'];
    $new_equipment->purchased_date = mktime(0, 0, 0, $e['month']);
    $new_equipment->require_training = 1;
    $new_equipment->accept_reserv = 1;
    $new_equipment->accept_sample = 1; 

    if ($new_equipment->save()) {
        secho(T("仪器%equipment_name创建成功\n", ['%equipment_name'=>$new_equipment->name])); 

        $new_equipment->connect($super_user, 'contact');
        $new_equipment->connect($super_user, 'incharge');

        if ($e['equipment_tag']) {
            //关联仪器分类标签
            Tag_Model::replace_tags($new_equipment, $e['equipment_tag'], 'equipment');
        }
        else {
            $equipment_root_tag->connect($new_equipment); 
        }

        if ($e['group_tag']) {
            //关联仪器组织机构标签
            $group_tag = O('tag', ['name'=>$e['group_tag']]);
            $new_equipment->group = $group_tag->id ? $group_tag : Tag_Model::root('group'); 
            $group_tag->connect($new_equipment);
            $new_equipment->save();
        }
        else {
            $group_root_tag->connect($new_equipment); 
        }
    }
    else {
        fecho(T("仪器%equipment_name创建失败\n", ['%equipment_name'=>$new_equipment->name])); 
    }
}

//生成团体培训记录
foreach($ge_trainings as $ge) {

    $new_ge_training = O('ge_training');
    $new_ge_training->user = $super_user;
    $new_ge_training->equipment = O('equipment', ['name'=>$ge['equipment_name']]);
    $new_ge_training->ntotal = $ge['ntotal'];
    $new_ge_training->napproved = $ge['napproved'];
    $new_ge_training->date = $now;
    if($new_ge_training->save()) {
        secho(T("%equipment_name的团体培训添加成功\n", ['%equipment_name'=>$ge['equipment_name']])); 
    }
    else {
        fecho(T("%equipment_name的团体培训添加失败\n", ['%equipment_name'=>$ge['equipment_name']])); 
    }
}

//生成个人培训记录
foreach($ue_trainings as $ue) {
    $new_ue_trainig = O('ue_training');
    $new_ue_trainig->status = UE_Training_Model::STATUS_APPROVED;
    $new_ue_trainig->user = O('user', ['name'=>$ue['user_name']]);
    $new_ue_trainig->equipment = O('equipment', ['name'=>$ue['equipment_name']]);
    if ($new_ue_trainig->save()) {
        secho(T("%user_name在%equipment_name的个人培训申请增加成功\n", ['%user_name'=>$ue['user_name'], '%equipment_name'=>$ue['equipment_name']])); 
    }
    else {
        fecho(T("%user_name在%equipment_name的个人培训申请增加失败\n", ['%user_name'=>$ue['user_name'], '%equipment_name'=>$ue['equipment_name']])); 
    }
}

//生成项目
foreach($projects as $p) {

    $new_project = O('lab_project'); 
    $new_project->lab = $super_lab;
    $new_project->name = $p['name'];
    switch($p['type']) {
        case 'EDUCATION' : 
            $new_project->type = Lab_Project_Model::TYPE_EDUCATION;
            break;
        case 'RESEARCH' :
            $new_project->type = Lab_Project_Model::TYPE_RESEARCH;  
            break;
        case 'SERVICE' :
            $new_project->type = Lab_Project_Model::TYPE_SERVICE;  
            break;
    }
    if ($new_project->save()) {
        secho(T("实验室项目%prject_name创建成功\n", ['%prject_name'=>$new_project->name])); 
    }
    else {
        fecho(T("实验室项目%prject_name创建失败\n", ['%prject_name'=>$new_project->name])); 
    }
}

//生成record
foreach($records as $r) {

    $equipment = O('equipment', ['name'=>$r['equipment_name']]);
    $user = O('user', ['name'=>$r['user_name']]);
    if ($r['is_reserv']) {

        $new_calendar = O('calendar', ['name'=>T('%equipment_name的预约', ['%equipment_name'=>$equipment->name])]);

        if (!$new_calendar->id) {
            $new_calendar->name = T('%equipment_name的预约', ['%equipment_name'=>$equipment->name]);
            $new_calendar->parent = $equipment;
            $new_calendar->parent_name = 'equipment';
            $new_calendar->save();
        }

        $new_cal_component = O('cal_component');
        $new_cal_component->calendar = $new_calendar;
        $new_cal_component->name = '仪器使用预约';
        $new_cal_component->organizer_id = $user->id ;
        $record_time = get_equipment_record_time($equipment, $r['record_time']);
        $new_cal_component->dtstart = $record_time['dtstart'];
        $new_cal_component->dtend = $record_time['dtend'];
        if($new_cal_component->save()) {
            secho(T("%user_name 在%equipment_name上的预约添加成功\n", ['%user_name'=>$r['user_name'], '%equipment_name'=>$r['equipment_name']]));    
        }
        else {
            fecho(T("%user_name 在%equipment_name上的预约添加失败\n", ['%user_name'=>$r['user_name'], '%equipment_name'=>$r['equipment_name']]));    
        }
    }
    else {
        $new_record = O('eq_record');
        $equipment = O('equipment', ['name'=>$r['equipment_name']]);
        $new_record->equipment = $equipment; 
        $new_record->user = $user;
        $new_record->samples = $r['samples'];
        $record_time = get_equipment_record_time($equipment, $r['record_time']);
        $new_record->dtstart = $record_time['dtstart'];
        $new_record->dtend = $record_time['dtend'];
        $new_record->status = EQ_Record_Model::FEEDBACK_NORMAL;//正常使用
        $new_record->project = O('lab_project', ['name'=>'lims开发']);
    }

    if ($new_record->save()) {
        secho(T("%user_name在%equipment_name上的使用记录添加成功\n", ['%user_name'=>$r['user_name'], '%equipment_name'=>$r['equipment_name']])); 
    }
    else {
        fecho(T("%user_name在%equipment_name上的使用记录添加失败\n", ['%user_name'=>$r['user_name'], '%equipment_name'=>$r['equipment_name']])); 
    }
}

function get_equipment_record_time($equipment, $record_long_time, $is_reserv = FALSE) {
//获取随机的record时间函数
//equpment,仪器，$record_long_time，记录时长
    
    $now = time();

    if (!$is_reserv) {
        $dtstart = rand(mktime(0, 0, 0, date('m', $now), date('d', $now) - 3), $now);
        //随机获取一个dtstart,当月到当前的随机的一个dtstart

        $dtend =  $dtstart + $record_long_time * 60 * 60 - 1;
        //根据随机的dtstart获取dtend

        if (!Q("eq_record[equipment={$equipment}][dtstart=$dtstart~$dtend][dtend=$dtstart~$dtend]")->total_count()) {
            //如果eq_record不冲突，那么返回随机获取的dtstart和dtend
            return [
                'dtstart'=>$dtstart,
                'dtend'=>$dtend
            ];
        }
        else {
            return get_equipment_record_time($equipment, $record_long_time); 
            //返回自己,递归函数
        }
    }
    else {
        $dtend = rand(mktime(23, 59, 59, date('m', $now), date('d', $now) + 2), $now); 
        $dtstart = $dtend - $record_long_time * 60 * 60 - 1;
        $equipment_calendar = Q("calendar[parent_id={$equipment->id}]:limit(1)")->current();
        if (!Q("cal_component[calendar={$equipment_calendar}][dtstart=$dtstart~$dtend][dtend=$dtstart~$dtend]")->total_count()) {
            return [
                'dtstart'=>$dtstart,
                'dtend'=>$dtend
            ]; 
        }
        else {
            return get_equipment_record_time($equipment, $record_long_time, TRUE); 
        }
    }
}


//生成论文
foreach($publications as $p) {

    $new_publication = O('publication');
    $new_publication->title= $p['name'];
    $new_publication->lab = $super_lab;
    $new_publication->date = $now;

    if($new_publication->save()) {
        secho(T("论文 %publication_name 创建成功\n", ['%publication_name'=>$p['name']])); 

        foreach($p['equipments'] as $e) {
            $new_publication->connect(O('equipment', ['name'=>$e]));
        }
        $new_publication->connect(O('lab_project', ['name'=>$p['project']]));
        //关联实验室项目
    }
    else {
        fecho(T("论文 %publication_name 创建失败\n", ['%publication_name'=>$p['name']])); 
    }
}

function get_true_result($type) {
    //获取真实计算结果

    global $equipment_root_tag;//这种global写法正确吗
    global $group_root_tag;

    
    $temp_array = array_merge(Eq_Stat::do_stat($equipment_root_tag->id, $type, 'y', NULL, NULL), Eq_Stat::do_stat($group_root_tag->id, $type, 'y', NULL, NULL));  
    $true_result = [];

    foreach($temp_array as $t) {
        $tag_name = $t['label'];

        if($tag_name == '其他' && !isset($true_result['仪器分类'])) {
            $tag_name = '仪器分类'; 
        }
        elseif ($tag_name == '其他') {
            $tag_name = '组织机构'; 
        }

        //这段写得好烂,先从仪器分类的tag开始循环，遇到的第一个其他是仪器分类
        $result = $t['points'][0]['value'];
        $true_result[$tag_name] = $result;
    }

    return  $true_result;
}

//仪器台数测试开始

hecho("仪器台数测试开始\n");

$equipment_count_true_result = get_true_result('equipments_count');

foreach($equipment_count as $ec) {

    if ($equipment_count_true_result[ ($ec['tag_name'])] == $ec['result']) {
        secho(T("标签 %tag_name 下的仪器台数与期望相同, 都为 %result\n", ['%tag_name'=>$ec['tag_name'], '%result'=>$ec['result']])); 
    }
    else {
        fecho(T("标签 %tag_name 下的仪器台数与期望不同,期望结果为 %result, 实际结果为 %true_result\n", ['%tag_name'=>$ec['tag_name'], '%result'=>$ec['result'], '%true_result'=>$equipment_count_true_result[($ec['tag_name'])]])); 
    }
}

//仪器台数测试结束

//仪器总值测试开始

hecho("仪器总值测试开始\n");

$equipment_value_true_result = get_true_result('equipments_value');

foreach($equipment_value as $ev) {

    if ($equipment_value_true_result[($ev['tag_name'])] == $ev['result']) {
        secho(T("标签 %tag_name 下的仪器总值与期望相同，都为 %result \n", ['%tag_name'=>$ev['tag_name'], '%result'=>$ev['result']])); 
    }
    else {
        fecho(T("标签 %tag_name 下的仪器总值与期望不同，期望结果为 %result, 实际结果为 %true_result \n", ['%tag_name'=>$ev['tag_name'], '%result'=>$ev['result'], '%true_result'=>$equipment_value_true_result[($ev['tag_name'])]])); 
    }
}

//仪器总值测试结束

//仪器培训人数测试开始
hecho("仪器培训人数测试开始\n");

$equipment_total_tainees_result = get_true_result('total_trainees');

foreach($total_trainees as $tt) {
    
    if ($equipment_total_tainees_result[($tt['tag_name'])] == $tt['result']) {
        secho(T("标签 %tag_name 下的仪器培训人数与期望相同, 都为%result\n", ['%tag_name'=>$tt['tag_name'], '%result'=>$tt['result']]));
    }
    else {
        fecho(T("标签 %tag_name 下的仪器培训人数与期望不同, 期望结果为%result, 实际结果为 %true_result\n", ['%tag_name'=>$tt['tag_name'], '%result'=>$tt['result'], '%true_result'=>$equipment_total_tainees_result[($tt['tag_name'])]]));
    }
}

//仪器培训人数测试结束

//论文总数测试开始
hecho("论文总数测试开始\n");

$equipment_pubs_result = get_true_result('pubs');

foreach($pubs  as $p) {

    if ($equipment_pubs_result[($p['tag_name'])] == $p['result']) {
        secho(T("标签 %tag_name 下的论文数与期望相同, 都为%result\n", ['%tag_name'=>$p['tag_name'], '%result'=>$p['result']]));
    }
    else {
        fecho(T("标签 %tag_name 下的论文数与期望不同, 期望结果为%result, 实际结果为 %true_result\n", ['%tag_name'=>$p['tag_name'], '%result'=>$p['result'], '%true_result'=>$equipment_pubs_result[($p['tag_name'])]]));
    }
}


//使用次数测试开始

hecho("使用次数测试开始\n");

$equipment_use_time = get_true_result('use_time');

foreach($use_time as $ut) {

    if ($equipment_use_time[($ut['tag_name'])] == $ut['result']) {
       secho(T("标签 %tag_name 下的仪器使用次数与期望相同，都为 %result\n", ['%tag_name'=>$ut['tag_name'], '%result'=>$ut['result']]));  
    }
    else {
       fecho(T("标签 %tag_name 下的仪器使用次数与期望不同，期望结果为%result, 实际结果为 %true_result\n", ['%tag_name'=>$ut['tag_name'], '%result'=>$ut['result'], '%true_result'=>$equipment_use_time[($ut['tag_name'])]]));  
    }
}

//使用次数测试结束

//测样数测试开始

hecho("测样数测试开始\n");

$equipment_record_sample = get_true_result('record_sample');

foreach($record_sample as $rs) {

    if ($equipment_record_sample[($rs['tag_name'])] == $rs['result']) {
       secho(T("标签 %tag_name 下的测样数与期望相同，都为 %result\n", ['%tag_name'=>$rs['tag_name'], '%result'=>$rs['result']]));  
    }
    else {
       fecho(T("标签 %tag_name 下的仪器测样数与期望不同，期望结果为%result, 实际结果为 %true_result\n", ['%tag_name'=>$rs['tag_name'], '%result'=>$rs['result'], '%true_result'=>$equipment_record_sample[($rs['tag_name'])]]));  
    }

}

//测样数测试结束

//仪器使用机时测试开始

hecho("仪器使用机时测试开始\n");

$equipment_time_total = get_true_result('time_total');

foreach($time_total as $tc) {

    if ($equipment_time_total[($tc['tag_name'])] == $tc['result']) {
       secho(T("标签 %tag_name 下的使用机时与期望相同，都为 %result\n", ['%tag_name'=>$tc['tag_name'], '%result'=>$tc['result']]));  
    }
    else {
       fecho(T("标签 %tag_name 下的使用机时间与期望不同，期望结果为%result, 实际结果为 %true_result\n", ['%tag_name'=>$tc['tag_name'], '%result'=>$tc['result'], '%true_result'=>$equipment_time_total[($tc['tag_name'])]]));  
    }
}
//仪器使用机时测试结束

//仪器开放机时测试开始

hecho("仪器开放机时测试开始\n");

$equipment_time_open = get_true_result('time_open');

foreach($time_open as $to) {

    if ($equipment_time_open[($to['tag_name'])] == $to['result']) {
       secho(T("标签 %tag_name 下的开放机时与期望相同，都为 %result\n", ['%tag_name'=>$to['tag_name'], '%result'=>$to['result']]));  
    }
    else {
       fecho(T("标签 %tag_name 下的开放机时间与期望不同，期望结果为 %result, 实际结果为 %true_result\n", ['%tag_name'=>$to['tag_name'], '%result'=>$to['result'], '%true_result'=>$equipment_time_open[($to['tag_name'])]]));  
    }

}

//仪器开放机时测试结束


//测试之后数据库还原

echo T("是否恢复当前数据库信息到测试之前状态? y/n \n");

$stdin = trim(strtolower(fgets(STDIN)));

if ($stdin == 'y') {

    if($db->restore($dbfile)) {
        secho("数据库信息已恢复\n"); 
    }
    else {
        fecho("数据库数据恢复失败!\n");  
    }

}
else {
    echo "数据库信息未恢复,请查看数据库信息，比对错误结果\n";
}
