<?php
 /*
 * @file  environment.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 实验室的测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/10-labs/environment
 */

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:Labs模块\n\n";

Environment::init_site();

$group = Environment::add_group("南开大学");

$role1 = Environment::add_role("系统实验室管理员",['添加/修改实验室','添加/移除所有实验室的成员']);
$role2 = Environment::add_role("组织机构实验室负责人",['添加/修改下属机构实验室']);

$user1 = Environment::add_user('许宏山');
$user2 = Environment::add_user('柴志华');
$user3 = Environment::add_user('程莹');
$user4 = Environment::add_user('陈建宁');

$lab   = Environment::add_lab('程莹课题组', $user3, $group);

//许宏山角色为组织机构实验室负责人，组织机构为南开大学
Environment::set_role($user1,$role2);
Environment::set_group($user1, $group);

//柴志华角色为实验室管理员
Environment::set_role($user2, $role1);

//程莹为程莹课题组PI
Environment::set_lab($user3, $lab);

echo "\n环境生成完毕\n";
