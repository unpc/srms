<?php 
/*
 * @file match_time_rule.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 测试基类door的match_time_rule函数功能是否正常
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/20-entrance/match_time_rule
 *
 * @ yday    0~365     //某年的哪一天
 * @ mon     1~12      //某个月
 * @ mday    1~31      //某个月的哪一天
 * @ wday    -3~6      //某周的周几   0～6为周日~周六、-1为day(每天)、-2为weekday(工作日)、-3为weekend_day(周末)
 * @ hours   0~23      //哪个小时
 * @ minutes 0~59      //哪个分
 * @ yweek   1-53      //某年的第几周
 * @ mweek   1~5       //某月的第几周
 */


////////////////////////////////////////  设置环境 /////////////////////////////////
//以日计算
if (!Module::is_installed('entrance')) return true;
/*
 *@ 在 2010-7-22 00:00:00 ~ 2010-8-1 00:00:00 这段时间，每4天的 9:00:00 ～ 18:00:00有效
 */
$rule1 = [
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_DAILY,
	'rnum' => 4,
];

$rule1_date1 = strtotime('2010-6-20 9:20:30');     //非法时间
$rule1_date2 = strtotime('2010-8-23 9:20:30');     //合法时间
$rule1_date3 = strtotime('2010-8-26 9:20:30');     //非法时间

//以周计算
/*
 *@ 在 2010-7-22 00:00:00 ~ 2010-8-1 00:00:00 这段时间，每两周的周三或者周四的 9:00:00 ～ 18:00:00有效
 */
$rule2 = [
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_WEEKLY,
	'rnum' => 2,
	'rrule' => [[4,5,-2]],   
];

$rule2_date1 = strtotime('2010-6-20 9:20:30');			   //非法时间
$rule2_date2 = strtotime('2010-8-25 9:20:30'); // 周三     //合法时间
$rule2_date3 = strtotime('2010-8-26 9:20:30'); // 周四     //非法时间
$rule2_date4 = strtotime('2010-8-28 9:20:30'); // 周六     //非法时间

//以月计算
/*
 *@ 在 2010-7-22 00:00:00 ~ 2010-8-1 00:00:00 这段时间，每月的第二周或者第四周的周三或者周五的 9:00:00 ～ 18:00:00有效
 */
 
//第一组
$rule3 = [
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_MONTHLY,
	'rnum' => 1,
	'rrule' => [[2,3,4], [3,5]],         //array(array(2,3), array(3,5))   //array(array(25,27))
	//'rrule' => array(array(25,27)),
];

$rule3_date1 = strtotime('2010-6-20 9:20:30');
$rule3_date2 = strtotime('2010-8-26 9:20:30');   //第四周的周四
$rule3_date3 = strtotime('2010-8-27 9:20:30');   //第四周的周五

//第二组
$rule3_1 = [
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_MONTHLY,
	'rnum' => 1,
	'rrule' => [[25,27]],
];

$rule3_1_date1 = strtotime('2010-6-20 9:20:30');
$rule3_1_date2 = strtotime('2010-8-26 9:20:30');   //第四周的周四
$rule3_1_date3 = strtotime('2010-8-27 9:20:30');   //第四周的周五


//以年计算
/*
 *@ 在 2010-7-22 00:00:00 ~ 2010-8-1 00:00:00 这段时间，每年的第三月份或者第七月份的第二周、第三周、第五周的周三或者周五的 9:00:00 ～ 18:00:00有效
 */
//第一组数据
$rule4 = [
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_YEARLY,
	'rnum' => 1,
	'rrule' => [[3,8], [2,3,4], [3,4,5]],  //某年的第几个月的第几周的周几
  //'rrule' => array(array(3,100)),                          
];

$rule4_date1 = strtotime('2010-6-20 9:20:30');    //非法数据
$rule4_date2 = strtotime('2010-8-26 9:20:30');    //周四
$rule4_date3 = strtotime('2010-8-27 9:20:30');    //周五

//第二组数据
$rule4_1 = [
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_YEARLY,
	'rnum' => 1,
	'rrule' => [[6,8], [-1], [26,27]],     //某年的某月的几号
];

$rule4_1_date1 = strtotime('2010-6-20 9:20:30');    //非法数据
$rule4_1_date2 = strtotime('2010-8-26 9:20:30');    //周四
$rule4_1_date3 = strtotime('2010-8-27 9:20:30');    //周五  

//第三组数据
$rule4_2 = [
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_YEARLY,
	'rnum' => 1,
	'rrule' => [[34,35], [4,5]],     //某年的某周的周几
];

$rule4_2_date1 = strtotime('2010-6-20 9:20:30');    //非法数据
$rule4_2_date2 = strtotime('2010-8-26 9:20:30');    //周四
$rule4_2_date3 = strtotime('2010-8-20 9:20:30');    

//第四组数据
$rule4_3 = [
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_YEARLY,
	'rnum' => 1,
	'rrule' => [[237,238,200]],     //某年的哪些天
];

$rule4_3_date1 = strtotime('2010-6-20 9:20:30');    //非法数据
$rule4_3_date2 = strtotime('2010-8-26 9:20:30');    //周四
$rule4_3_date3 = strtotime('2010-8-20 9:20:30'); 

//////////////////////////////// 测试开始 ///////////////////////////////////////////

//检查Door是否存在
Unit_Test::assert('Door Helper存在', class_exists('Door', TRUE));
Unit_Test::assert('TM_RRule Helper存在', class_exists('TM_RRule', TRUE));
Unit_Test::echo_endl();

//以日计算
Unit_Test::echo_title("测试以日计算");
	Unit_Test::assert(date('Y-m-d H:i:s', $rule1_date1).'=>FALSE', TM_RRule::match_time_rule($rule1_date1, $rule1) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule1_date2).'=>TRUE', TM_RRule::match_time_rule($rule1_date2, $rule1) == TRUE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule1_date3).'=>FALSE', TM_RRule::match_time_rule($rule1_date3, $rule1) == FALSE);
Unit_Test::echo_endl();

//以周计算
Unit_Test::echo_title("测试以周计算");
	Unit_Test::assert(date('Y-m-d H:i:s', $rule2_date1).'=>FALSE', TM_RRule::match_time_rule($rule2_date1, $rule2) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule2_date2).'=>TRUE', TM_RRule::match_time_rule($rule2_date2, $rule2) == TRUE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule2_date3).'=>FALSE', TM_RRule::match_time_rule($rule2_date3, $rule2) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule2_date4).'=>FALSE', TM_RRule::match_time_rule($rule2_date4, $rule2) == FALSE);
Unit_Test::echo_endl();

//以月计算
Unit_Test::echo_title("测试以月计算");
Unit_Test::echo_title("第一组");
	Unit_Test::assert(date('Y-m-d H:i:s', $rule3_date1).'=>FALSE', TM_RRule::match_time_rule($rule3_date1, $rule3) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule3_date2).'=>FALSE', TM_RRule::match_time_rule($rule3_date2, $rule3) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule3_date3).'=>TRUE', TM_RRule::match_time_rule($rule3_date3, $rule3) == TRUE);
Unit_Test::echo_endl();

Unit_Test::echo_title("第二组");
	Unit_Test::assert(date('Y-m-d H:i:s', $rule3_1_date1).'=>FALSE', TM_RRule::match_time_rule($rule3_1_date1, $rule3_1) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule3_1_date2).'=>FALSE', TM_RRule::match_time_rule($rule3_1_date2, $rule3_1) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule3_1_date3).'=>TRUE', TM_RRule::match_time_rule($rule3_1_date3, $rule3_1) == TRUE);
Unit_Test::echo_endl();

//以年计算
Unit_Test::echo_title("测试以年计算");
Unit_Test::echo_title("第一组");
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_date1).'=>FALSE', TM_RRule::match_time_rule($rule4_date1, $rule4) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_date2).'=>TRUE', TM_RRule::match_time_rule($rule4_date2, $rule4) == TRUE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_date3).'=>TRUE', TM_RRule::match_time_rule($rule4_date3, $rule4) == TRUE);
Unit_Test::echo_endl();

Unit_Test::echo_title("第二组");
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_1_date1).'=>FALSE', TM_RRule::match_time_rule($rule4_1_date1, $rule4_1) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_1_date2).'=>TRUE', TM_RRule::match_time_rule($rule4_1_date2, $rule4_1) == TRUE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_1_date3).'=>TRUE', TM_RRule::match_time_rule($rule4_1_date3, $rule4_1) == TRUE);
Unit_Test::echo_endl();

Unit_Test::echo_title("第三组");
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_2_date1).'=>FALSE', TM_RRule::match_time_rule($rule4_2_date1, $rule4_2) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_2_date2).'=>TRUE', TM_RRule::match_time_rule($rule4_2_date2, $rule4_2) == TRUE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_2_date3).'=>FALSE', TM_RRule::match_time_rule($rule4_2_date3, $rule4_2) == FALSE);
Unit_Test::echo_endl();

Unit_Test::echo_title("第四组");
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_3_date1).'=>FALSE', TM_RRule::match_time_rule($rule4_3_date1, $rule4_3) == FALSE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_3_date2).'=>TRUE', TM_RRule::match_time_rule($rule4_3_date2, $rule4_3) == TRUE);
	Unit_Test::assert(date('Y-m-d H:i:s', $rule4_3_date3).'=>FALSE', TM_RRule::match_time_rule($rule4_3_date3, $rule4_3) == FALSE);
Unit_Test::echo_endl();


