<?php
/*
 * @file grants.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 经费模块测试用例环境搭建脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/grants/grants
 */
require_once(ROOT_PATH . 'unit_test/helpers/environment.php');
echo "开始环境自动生成:Grants模块\n\n";

Environment::init_site();

$user1 = Environment::add_user('陈建宁');
$user2 = Environment::add_user('程莹');
$user3 = Environment::add_user('柴志华');

$role1 = Environment::add_role('经费管理员', ['管理所有经费', '查看经费模块']);
$role2 = Environment::add_role('经费模块查看人员', ['查看经费模块']);

Environment::set_role($user1, $role1);
Environment::set_role($user2, $role2);

echo "\n环境生成完毕\n";
