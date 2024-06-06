<?php

/*
 * @file environment.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 仪器送样模块测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_sample/environment
 */
if (!Module::is_installed('eq_sample') || !Module::is_installed('billing')) return true;
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:eq_sample模块\n\n";

$GLOBALS['preload']['billing.single_department'] = TRUE;

Environment::init_site();

$root_user = O('user', ['name'=>'技术支持']);

$role1 = Environment::add_role('仪器送样管理员',[
	'修改所有仪器的送样设置',
	'修改所有仪器的送样',
	'添加/修改所有机构的仪器',
]);
$role2 = Environment::add_role('仪器负责人',[
	'修改负责仪器的送样设置',
	'修改负责仪器的送样',
]);
$role3 = Environment::add_role('送样锁定管理员',[
	'添加/修改所有机构的仪器',
	'修改所有仪器的送样设置',
]);

$lab = Environment::add_lab('计费测试使用实验室', $root_user);

$user1 = Environment::add_user('程莹');
$user2 = Environment::add_user('许宏山');
$user3 = Environment::add_user('陈建宁');
$user4 = Environment::add_user('柴志华');
$user5 = Environment::add_user('胡宁');

Environment::set_lab($user1, $lab);
Environment::set_lab($user2, $lab);
Environment::set_lab($user3, $lab);
Environment::set_lab($user4, $lab);
Environment::set_lab($user5, $lab);

$department = Billing_department::get();
$account = Environment::add_account($lab, $department);
Environment::add_transaction($account, $root_user, 20000);

Environment::set_role($user1, $role1);
Environment::set_role($user2, $role2);
Environment::set_role($user3, $role3);

$equ1  = Environment::add_equipment('400M核磁-1', $user2 ,$user2);
$equ2  = Environment::add_equipment('400M核磁-2', $user5 ,$user5);
