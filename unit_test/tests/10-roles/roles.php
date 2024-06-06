<?php
/*
* @file roles.php 
* @author Jia Huang <jia.huang@geneegroup.com>
* @date 2012-07-02
* 
* @brief 角色模块环境架设脚本
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/
*/

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:Roles模块\n\n";

Environment::init_site();

$user1 = Environment::add_user('许宏山');
$user2 = Environment::add_user('柴志华');

$role = Environment::add_role('权限管理的管理员', ['管理分组', '添加/修改所有成员信息']);

Environment::set_role($user1, $role);

echo "\n环境生成完毕\n";
