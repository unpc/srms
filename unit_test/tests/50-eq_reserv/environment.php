<?php

/*
 * @file environment.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 仪器预约模块测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_reserv/environment
*/
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:eq_reserv模块\n\n";

Environment::init_site();

Environment::set_config('equipment.reserv_max_time', 60*60*24*7);
Environment::set_config('equipment.modify_time_limit', 60*60);

$user1 = Environment::add_user('陈建宁');
$user2 = Environment::add_user('许宏山');
$user3 = Environment::add_user('胡宁');
$user4 = Environment::add_user('程莹');
$user5 = Environment::add_user('柴志华');

$equ1  = Environment::add_equipment('预约测试仪器', $user2, $user1);
Environment::equ_add_tag($equ1, 'VIP', $user4);
$equ1->accept_reserv = true;
$equ1->save();

$role1 = Environment::add_role('仪器管理员',[
	'修改所有仪器的预约设置',
	'为所有仪器添加预约',
	'为所有仪器添加重复预约事件',
	'删除所有仪器的预约',
	'修改所有仪器标签',
	'修改所有仪器的预约',
	'添加/修改所有机构的仪器',
	'修改所有仪器的使用记录',
	'修改所有仪器的使用设置'
]);

$role2 = Environment::add_role('仪器负责人', [
	'修改负责仪器的预约设置',
	'修改负责仪器的预约',
	'为负责仪器添加预约',
	'为负责仪器添加重复预约事件',
	'删除负责仪器的预约',
	'修改负责仪器的使用设置',
	'修改负责仪器的使用记录'
]);

Environment::set_role($user3, $role1);
Environment::set_role($user2, $role2);
