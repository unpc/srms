<?php
/*
 * @file environment.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 日程模块测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/20-schedule/environment
 */

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:schedule模块\n\n";

Environment::init_site();

$user1 = Environment::add_user('刘成');
$user2 = Environment::add_user('吴天放');
$user3 = Environment::add_user('吴凯');
$user4 = Environment::add_user('马睿');

$role1 = Environment::add_role('日程查看者',[
	'查看所有成员的日程安排',
	'查看所有成员的日程附件',
]);
$role2 = Environment::add_role('日程管理者',[
	'管理所有成员的日程安排',
	'查看所有成员的日程安排',
	'管理所有成员的日程附件',
	'查看所有成员的日程附件',
]);

Environment::set_role($user1, $role2);
Environment::set_role($user3, $role1);
