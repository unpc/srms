<?php 
/*
 * @file  match_rule.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 测试基类door的match_rule函数功能是否正常
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/20-entrance/match_rule
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
//'users'=>json_decode('{"5":"刘振 (atar)", "295":"刘成 (LIMS开发人员测试实验室)", "293":"朱洪杰 (LIMS2)"}'),
if (!Module::is_installed('entrance')) return true;
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:entrance模块\n\n";

Environment::init_site();

$role1 = Environment::add_role('门禁管理员',[
	'查看门禁模块',
	'管理所有门禁',
]);

$role2 = Environment::add_role('门禁负责人',[
	'查看门禁模块',
]);

$user1 = Environment::add_user('许宏山');
$user2 = Environment::add_user('程莹');
$user3 = Environment::add_user('陈建宁');
$user4 = Environment::add_user('刘振');
$user5 = Environment::add_user('刘成');
$user6 = Environment::add_user('朱洪杰');

$group1 = Environment::add_group('理工大学');

$lab1  = Environment::add_lab('atar', $user4, $group1);
$lab2  = Environment::add_lab('LIMS开发人员测试实验室', $user5, $group1);
$lab3  = Environment::add_lab('LIMS2', $user6, $group1);

Environment::set_role($user1, $role1);
Environment::set_role($user2, $role2);

Environment::add_door('前门', $user2);
Environment::add_door('后门');
$rule1 = [
	'select_user_mode'=>'user',
	'users'=>json_decode('{"5":"刘振 (atar)", "6":"刘成 (LIMS开发人员测试实验室)", "7":"朱洪杰 (LIMS2)"}'),
	'directions'=>[1],
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_DAILY,
	'rnum' => 4,
];

$rule1_date1 = strtotime('2010-8-23 9:20:30');     //合法时间
$rule1_user1 = O('user', 5);
$rule1_direction1 = TRUE;

$rule1_date2 = strtotime('2010-8-23 9:20:30');     //合法时间
$rule1_user2 = O('user', 2);
$rule1_direction2 = TRUE;

$rule1_date3 = strtotime('2010-8-23 9:20:30');     //合法时间
$rule1_user3 = O('user', 5);
$rule1_direction3 = FALSE;

//以周计算
$rule2 = [
	'select_user_mode'=>'user',
	'users'=>json_decode('{"5":"刘振 (atar)", "295":"刘成 (LIMS开发人员测试实验室)", "7":"朱洪杰 (LIMS2)"}'),
	'directions'=>[1],
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_WEEKLY,
	'rnum' => 2,
	'rrule' => [[4,5,-2]],   
];

$rule2_date1 = strtotime('2010-8-25 9:20:30'); // 周三     //合法时间
$rule2_user1 = O('user', 5);
$rule2_direction1 = TRUE;

$rule2_date2 = strtotime('2010-8-25 9:20:30'); // 周三     //合法时间
$rule2_user2 = O('user', 291);
$rule2_direction2 = TRUE;

$rule2_date3 = strtotime('2010-8-25 9:20:30'); // 周三     //合法时间
$rule2_user3 = O('user', 5);
$rule2_direction3 = FALSE;

//以月计算
$rule3 = [
	'select_user_mode'=>'user',
	'users'=>json_decode('{"5":"刘振 (atar)", "6":"刘成 (LIMS开发人员测试实验室)", "7":"朱洪杰 (LIMS2)"}'),
	'directions'=>[1],
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_MONTHLY,
	'rnum' => 1,
	'rrule' => [[2,3,4], [3,5]],         //array(array(2,3), array(3,5))   //array(array(25,27))
];

$rule3_date1 = strtotime('2010-8-27 9:20:30');   //第四周的周五
$rule3_user1 = O('user', 5);
$rule3_direction1 = TRUE;

$rule3_date2 = strtotime('2010-8-27 9:20:30');   //第四周的周五
$rule3_user2 = O('user', 2);
$rule3_direction2 = TRUE;

$rule3_date3 = strtotime('2010-8-27 9:20:30');   //第四周的周五
$rule3_user3 = O('user', 5);
$rule3_direction3 = FALSE;

//以年计算
$rule4 = [
	'select_user_mode'=>'user',
	'users'=>json_decode('{"5":"刘振 (atar)", "6":"刘成 (LIMS开发人员测试实验室)", "7":"朱洪杰 (LIMS2)"}'),
	'directions'=>[1],
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_YEARLY,
	'rnum' => 1,
	'rrule' => [[3,8], [2,3,4], [3,4,5]],  //某年的第几个月的第几周的周几
];

$rule4_date1 = strtotime('2010-8-26 9:20:30');    //周四
$rule4_user1 = O('user', 5);
$rule4_direction1 = TRUE;

$rule4_date2 = strtotime('2010-8-26 9:20:30');    //周四
$rule4_user2 = O('user', 2);
$rule4_direction2 = TRUE;

$rule4_date3 = strtotime('2010-8-26 9:20:30');    //周四
$rule4_user3 = O('user', 5);
$rule4_direction3 = FALSE;

///////////////////////////////////////////////////////////////////////////////////

//以日计算
$rule5 = [
	'select_user_mode'=>'lab',
	'labs'=>json_decode('{"2":"atar", "3":"LIMS2", "4":"LIMS开发人员测试实验室"}'),
	'directions'=>[1],
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_DAILY,
	'rnum' => 4,
];

$rule5_date1 = strtotime('2010-8-23 9:20:30');     //合法时间
$rule5_user1 = O('user', 5);
$rule5_direction1 = TRUE;

$rule5_date2 = strtotime('2010-8-23 9:20:30');     //合法时间
$rule5_user2 = O('user', 2);
$rule5_direction2 = TRUE;

$rule5_date3 = strtotime('2010-8-23 9:20:30');     //合法时间
$rule5_user3 = O('user', 5);
$rule5_direction3 = FALSE;

//以周计算
$rule6 = [
	'select_user_mode'=>'lab',
	'labs'=>json_decode('{"2":"atar", "3":"LIMS2", "4":"LIMS开发人员测试实验室"}'),
	'directions'=>[1],
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_WEEKLY,
	'rnum' => 2,
	'rrule' => [[4,5,-2]],   
];

$rule6_date1 = strtotime('2010-8-25 9:20:30'); // 周三     //合法时间
$rule6_user1 = O('user', 5);
$rule6_direction1 = TRUE;

$rule6_date2 = strtotime('2010-8-25 9:20:30'); // 周三     //合法时间
$rule6_user2 = O('user', 2);
$rule6_direction2 = TRUE;

$rule6_date3 = strtotime('2010-8-25 9:20:30'); // 周三     //合法时间
$rule6_user3 = O('user', 5);
$rule6_direction3 = FALSE;

//以月计算
$rule7 = [
	'select_user_mode'=>'lab',
	'labs'=>json_decode('{"2":"atar", "3":"LIMS2", "4":"LIMS开发人员测试实验室"}'),
	'directions'=>[1],
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_MONTHLY,
	'rnum' => 1,
	'rrule' => [[2,3,4], [3,5]],         //array(array(2,3), array(3,5))   //array(array(25,27))
];

$rule7_date1 = strtotime('2010-8-27 9:20:30');   //第四周的周五
$rule7_user1 = O('user', 5);
$rule7_direction1 = TRUE;

$rule7_date2 = strtotime('2010-8-27 9:20:30');   //第四周的周五
$rule7_user2 = O('user', 2);
$rule7_direction2 = TRUE;

$rule7_date3 = strtotime('2010-8-27 9:20:30');   //第四周的周五
$rule7_user3 = O('user', 5);
$rule7_direction3 = FALSE;

//以年计算
$rule8 = [
	'select_user_mode'=>'lab',
	'labs'=>json_decode('{"2":"atar", "3":"LIMS2", "4":"LIMS开发人员测试实验室"}'),
	'directions'=>[1],
	'dtfrom' => strtotime('2010-7-22 00:00:00'),
	'dtto' => strtotime('2010-8-30 00:00:00'),
	'dtstart' => strtotime('2010-7-26 9:00:00'),
	'dtend' => strtotime('2010-8-26 18:00:00'),
	'rtype' => TM_RRule::RRULE_YEARLY,
	'rnum' => 1,
	'rrule' => [[3,8], [2,3,4], [3,4,5]],  //某年的第几个月的第几周的周几
];

$rule8_date1 = strtotime('2010-8-26 9:20:30');    //周四
$rule8_user1 = O('user', 5);
$rule8_direction1 = TRUE;

$rule8_date2 = strtotime('2010-8-26 9:20:30');    //周四
$rule8_user2 = O('user', 2);
$rule8_direction2 = TRUE;

$rule8_date3 = strtotime('2010-8-26 9:20:30');    //周四
$rule8_user3 = O('user', 5);
$rule8_direction3 = FALSE;

//////////////////////////////// 测试开始 ///////////////////////////////////////////

//检查Door是否存在
Unit_Test::assert('Door Helper存在', class_exists('Door', TRUE));
//Unit_Test::echo_endl();

Unit_Test::echo_title("测试环境(select_user_mode为user)");

//以日计算
Unit_Test::assert('测试以日计算(TRUE)', Door::match_rule($rule1_user1, $rule1_direction1, $rule1_date1, $rule1) == TRUE);

Unit_Test::assert('测试以日计算(FALSE)', Door::match_rule($rule1_user2, $rule1_direction2, $rule1_date2, $rule1) == FALSE);
Unit_Test::assert('测试以日计算(FALSE)', Door::match_rule($rule1_user3, $rule1_direction3, $rule1_date3, $rule1) == FALSE);
Unit_Test::echo_endl();

//以周计算
Unit_Test::assert('测试以周计算(TRUE)', Door::match_rule($rule2_user1, $rule2_direction1, $rule2_date1, $rule2) == TRUE);
Unit_Test::assert('测试以周计算(FALSE)', Door::match_rule($rule2_user2, $rule2_direction2, $rule2_date2, $rule2) == FALSE);
Unit_Test::assert('测试以周计算(FALSE)', Door::match_rule($rule2_user3, $rule2_direction3, $rule2_date3, $rule2) == FALSE);
Unit_Test::echo_endl();

//以月计算
Unit_Test::assert('测试以月计算(TRUE)', Door::match_rule($rule3_user1, $rule3_direction1, $rule3_date1, $rule3) == TRUE);
Unit_Test::assert('测试以月计算(FALSE)', Door::match_rule($rule3_user2, $rule3_direction2, $rule3_date2, $rule3) == FALSE);
Unit_Test::assert('测试以月计算(FALSE)', Door::match_rule($rule3_user3, $rule3_direction3, $rule3_date3, $rule3) == FALSE);
Unit_Test::echo_endl();

//以年计算
Unit_Test::assert('测试以年计算(TRUE)', Door::match_rule($rule4_user1, $rule4_direction1, $rule4_date1, $rule4) == TRUE);
Unit_Test::assert('测试以年计算(FALSE)', Door::match_rule($rule4_user2, $rule4_direction2, $rule4_date2, $rule4) == FALSE);
Unit_Test::assert('测试以年计算(FALSE)', Door::match_rule($rule4_user3, $rule4_direction3, $rule4_date3, $rule4) == FALSE);
Unit_Test::echo_endl();

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
Unit_Test::echo_title("测试环境(select_user_mode为lab)");
//以日计算
Unit_Test::assert('测试以日计算(TRUE)', Door::match_rule($rule5_user1, $rule5_direction1, $rule5_date1, $rule5) == TRUE);
Unit_Test::assert('测试以日计算(FALSE)', Door::match_rule($rule5_user2, $rule5_direction2, $rule5_date2, $rule5) == FALSE);
Unit_Test::assert('测试以日计算(FALSE)', Door::match_rule($rule5_user3, $rule5_direction3, $rule5_date3, $rule5) == FALSE);
Unit_Test::echo_endl();

//以周计算
Unit_Test::assert('测试以周计算(TRUE)', Door::match_rule($rule6_user1, $rule6_direction1, $rule6_date1, $rule6) == TRUE);
Unit_Test::assert('测试以周计算(FALSE)', Door::match_rule($rule6_user2, $rule6_direction2, $rule6_date2, $rule6) == FALSE);
Unit_Test::assert('测试以周计算(FALSE)', Door::match_rule($rule6_user3, $rule6_direction3, $rule6_date3, $rule6) == FALSE);
Unit_Test::echo_endl();

//以月计算
Unit_Test::assert('测试以月计算(TRUE)', Door::match_rule($rule7_user1, $rule7_direction1, $rule7_date1, $rule7) == TRUE);
Unit_Test::assert('测试以月计算(FALSE)', Door::match_rule($rule7_user2, $rule7_direction2, $rule7_date2, $rule7) == FALSE);
Unit_Test::assert('测试以月计算(FALSE)', Door::match_rule($rule7_user3, $rule7_direction3, $rule7_date3, $rule7) == FALSE);
Unit_Test::echo_endl();

//以年计算
Unit_Test::assert('测试以年计算(TRUE)', Door::match_rule($rule8_user1, $rule8_direction1, $rule8_date1, $rule8) == TRUE);
Unit_Test::assert('测试以年计算(FALSE)', Door::match_rule($rule8_user2, $rule8_direction2, $rule8_date2, $rule8) == FALSE);
Unit_Test::assert('测试以年计算(FALSE)', Door::match_rule($rule8_user3, $rule8_direction3, $rule8_date3, $rule8) == FALSE);
Unit_Test::echo_endl();

