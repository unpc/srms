<?php

// 学期表
$config['school_term'] = [
    'fields' => [
        'year' => ['type' => 'varchar(50)','null' => false,'default' => ''],// 学年
        'term' => ['type' => 'int','null' => false,'default' => 0],// 学期类型
        'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],// 开始时间
		'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],// 结束时间
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes' => [
        'year' => ['fields' => ['year']]
    ],
];

// 学期-教学周（根据学期的日期自动生成）
$config['term_week'] = [
    'fields' => [
        'school_term' => ['type' => 'object','oname' => 'school_term'],// 学期
        'week' => ['type' => 'int','null' => FALSE, 'default' => 0],// 周        
        'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],// 开始时间
		'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],// 结束时间
    ],
    'indexes' => [
        'school_term' => ['fields' => ['school_term']]
    ],
];

// 学期-节次表
$config['course_session'] = [
    'fields' => [
        'term'    => ['type' => 'int','null' => false,'default' => 0],// 学期类型
        'session' => ['type' => 'int','null' => false,'default' => 0],// 节次
        'dtstart' => ['type'=>'int', 'null' => true, 'default' => 0],// 开始时间
		'dtend'   => ['type'=>'int', 'null' => true, 'default' => 0],// 结束时间
        'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes' => [
        'term' => ['fields' => ['term']],
        'session' => ['fields' => ['session']]
    ],
];


// 课程表
$config['course'] = [
    'fields' => [
        'school_term' => ['type' => 'object','oname' => 'school_term'],// 学期
        'ref_no' => ['type' => 'varchar(100)','null' => false,'default' => ''], // 课程代码
        'name' => ['type' => 'varchar(100)','null' => false,'default' => ''], // 课程名称
        'teacher' => ['type' => 'object', 'oname' => 'user'], // 教师
        'teacher_ref_no' => ['type' => 'varchar(50)','null' => false,'default' => ''], // 教师工号，老师不一定在系统存在吧
        'teacher_name' => ['type' => 'varchar(50)','null' => false,'default' => ''], // 教师姓名，老师不一定在系统存在吧
        'course_session' => ['type' => 'int','null' => false,'default' => 0],// 节次
        'week_day' => ['type' => 'int','null' => false,'default' => 0],// 星期几
        'week' => ['type' => 'varchar(500)','null' => false,'default' => ''],// 教学周 多个逗号隔开
        'classroom' => ['type' => 'object', 'oname' => 'meeting'], // 教室
        'classroom_ref_no' => ['type' => 'varchar(100)','null' => false,'default' => ''],//教室名称
        'classroom_name' => ['type' => 'varchar(100)','null' => false,'default' => ''],//教室编号
        'classbuild_name' => ['type' => 'varchar(100)','null' => false,'default' => ''],//教学楼名称
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes' => [
        'school_term' => ['fields' => ['school_term']],
        'name' => ['fields' => ['name']],
        'ref_no' => ['fields' => ['ref_no']],
        'ctime' => ['fields' => ['ctime']]
    ],
];

// 课程-教学周
$config['course_week'] = [
    'fields' => [
        'course' => ['type' => 'object','oname' => 'course'],// 学期
        'week' => ['type' => 'int','null' => FALSE, 'default' => 0],// 周   
    ],
    'indexes' => [
        'course' => ['fields' => ['course']],
        'week' => ['fields' => ['week']]
    ],
];