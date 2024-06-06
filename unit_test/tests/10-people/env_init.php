<?php
/*
* @file env_init.php
* @author Jia Huang <jia.huang@geneegroup.com>
* @date 2012-07-02
* 
* @brief 成员模块环境架设脚本
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/10-people/env_init
*/


require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:People模块\n\n";

Environment::init_site();

$user1 = Environment::add_user('许宏山');
$user2 = Environment::add_user('柴志华');
$user3 = Environment::add_user('程莹');
$user4 = Environment::add_user('陈建宁');

$role1 = Environment::add_role('系统成员管理员',['添加/修改所有成员信息','修改所有成员的角色','修改用户的预约违规次数']);
$role2 = Environment::add_role('组织机构成员管理员',['修改下属机构成员的角色','添加/修改下属机构成员的信息','修改用户的预约违规次数']);
$role3 = Environment::add_role('实验室成员管理员',['修改本实验室成员的角色','修改本实验室成员的信息','添加/移除本实验室成员','修改用户的预约违规次数']);

$group = Environment::add_group('南开大学');

$lab   = Environment::add_lab('陈建宁课题组', $user4);

// 柴志华，角色为系统成员管理员
Environment::set_role($user2, $role1);

//程莹，角色为组织机构成员管理员，组织机构为南开大学
Environment::set_role($user3, $role2);
Environment::set_group($user3, $group);

//陈建宁，角色为实验室成员管理员
Environment::set_role($user4, $role3);
Environment::set_lab($user4, $lab);


//系统中的非新增成员
$old_user1 = Environment::add_user('刘振');
$old_user2 = Environment::add_user('张国平');
$old_user3 = Environment::add_user('朱洪杰');
$old_user4 = Environment::add_user('陈铸焕');

Environment::set_group($old_user1, $group);
Environment::set_group($old_user2, $group);
Environment::set_group($old_user3, $group);
Environment::set_group($old_user4, $group);

Environment::set_lab($old_user1, $lab);
Environment::set_lab($old_user2, $lab);
Environment::set_lab($old_user3, $lab);
Environment::set_lab($old_user4, $lab);

echo "\n环境生成完毕\n";
