<?php
/*
* @file achievements.php 
* @author Jia Huang <jia.huang@geneegroup.com>
* @date 2012-07-02
* 
* @brief 成果管理模块测试用例环境架设脚本
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/20-achievements/achievements
*/


require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:Achievements模块\n\n";

Environment::init_site();

$user1 = Environment::add_user('胡宁');
$user2 = Environment::add_user('许宏山');
$user3 = Environment::add_user('柴志华');
$user4 = Environment::add_user('沈冰');

$role1 = Environment::add_role('系统成果管理员', ['查看所有实验室成果', '查看本实验室成果', '添加/修改所有实验室成果', '添加/修改本实验室成果']);
$role2 = Environment::add_role('实验室老师', ['查看本实验室成果', '添加/修改本实验室成果']);
$role3 = Environment::add_role('实验室学生', ['查看本实验室成果']);

$lab = Environment::add_lab('许宏山课题组', $user2);

Environment::set_role($user1, $role1);
Environment::set_role($user2, $role2);
Environment::set_role($user3, $role3);

Environment::set_lab($user3, $lab);

echo "\n环境生成完毕\n";
