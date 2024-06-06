<?php

/*
 * @file base.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 财务模块测试用例环境架设脚本(基本环境架设)
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/billing/base
 */
require_once(ROOT_PATH.'unit_test/helpers/environment.php');

echo "开始环境自动生成:Billing模块\n\n";
define('DISABLE_NOTIFICATION', TRUE);

Environment::init_site();

$user1 = Environment::add_user('刘磊');
$user2 = Environment::add_user('李明');
$user3 = Environment::add_user('张朝阳');
$user4 = Environment::add_user('王爽');
$user5 = Environment::add_user('朱亚东');
$user6 = Environment::add_user('赵志江');

$group1 = Environment::add_group('理工大学');
$group2 = Environment::add_group('电信学院', $group1);

$lab1 = Environment::add_lab('赵志江课题组', $user6, $group2);

$role1 = Environment::add_role('实验室管理员', ['列表本实验室的财务帐号', '列表本实验室的收支明细']);
$role2 = Environment::add_role('组织机构负责人', ['列表下属实验室的财务帐号', '列表下属实验室的收支明细']);
$role3 = Environment::add_role('财务管理员', ['管理财务中心']);

Environment::set_lab($user1, $lab1);
Environment::set_lab($user2, $lab1);
Environment::set_lab($user6, $lab1);

Environment::set_role($user1, $role1);
Environment::set_role($user3, $role2);
Environment::set_role($user5, $role3);

Environment::set_group($user3, $group1);
