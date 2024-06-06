<?php

/*
* @file privacy.php 
* @author Jia Huang <jia.huang@geneegroup.com>
* @date 2012-07-02
* 
* @brief 角色模块角色隐藏测试用例环境架设脚本
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/10-roles/privacy
*/

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:Roles模块\n\n";

Environment::init_site();

$user1 = Environment::add_user('吴天放');
$user2 = Environment::add_user('吴凯');
$user3 = Environment::add_user('刘成');
$user4 = Environment::add_user('刘磊');

$role1 = Environment::add_role('公开角色');
$role2 = Environment::add_role('组织机构角色');
$role3 = Environment::add_role('系统角色');
$role4 = Environment::add_role('组织机构管理员', ['添加/修改下属机构成员的信息']);
$role5 = Environment::add_role('系统管理员', ['添加/修改所有成员信息']);

Environment::set_role($user1, [$role1, $role2, $role3]);
Environment::set_role($user3, $role4);
Environment::set_role($user4, $role5);

Environment::set_role_privacy($role1, Role_Model::PRIVACY_ALL);
Environment::set_role_privacy($role2, Role_Model::PRIVACY_GROUP);
Environment::set_role_privacy($role3, Role_Model::PRIVACY_ADMIN);

echo "\n环境生成完毕\n";
