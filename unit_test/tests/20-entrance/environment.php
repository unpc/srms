<?php

/*
 * @file environment.php 
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 门禁模块测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/20-entrance/environment
 */

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

Environment::set_role($user1, $role1);
Environment::set_role($user2, $role2);

Environment::add_door('前门', $user2);
Environment::add_door('后门');
