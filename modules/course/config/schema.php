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
        'year' => ['fields' => ['year']],
        'dtstart' => ['fields' => ['dtstart']],
        'dtend' => ['fields' => ['dtend']],
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
        'term' => ['fields' => ['term']]
    ],
];

// 学期-教学周（根据学期的日期自动生成）
$config['course_week'] = [
    'fields' => [
        'school_term' => ['type' => 'object','oname' => 'school_term'],// 学期
        'week' => ['type' => 'int','null' => false,'default' => ''],// 周        
        'dtstart' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],// 开始时间
		'dtend' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],// 结束时间
    ],
    'indexes' => [
        'school_term' => ['fields' => ['school_term']]
    ],
];

// 课程代码	      课程名称	           教师工号	 教师姓名	节次	开始时间	结束时间	星期	教学周	      教室编号	教室名称	    教学楼名称
// 103900700	古建筑工程计量与计价	CJ0965	李文墨	    3	   10:15	 11:00	    1	   1,2,3,4,5,6	2202	教学南楼202	 教学南楼


// 课程表
$config['course'] = [
    'fields' => [
        'school_term' => ['type' => 'object','oname' => 'school_term'],// 学期
        'ref_no' => ['type' => 'varchar(100)','null' => false,'default' => ''], // 课程代码
        'name' => ['type' => 'varchar(100)','null' => false,'default' => ''], // 课程名称
        'teacher_ref_no' => ['type' => 'varchar(50)','null' => false,'default' => ''], // 教师工号，老师不一定在系统存在吧
        'teacher_name' => ['type' => 'varchar(50)','null' => false,'default' => ''], // 教师姓名，老师不一定在系统存在吧
        'course_session' => ['type' => 'int','null' => false,'default' => 0],// 节次
        'week_day' => ['type' => 'int','null' => false,'default' => 0],// 星期几
        'week' => ['type' => 'varchar(500)','null' => false,'default' => ''],// 教学周 多个逗号隔开
        // //教室编号	
        // //教室名称	   
        // //教学楼名称
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
