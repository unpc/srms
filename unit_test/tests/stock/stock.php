<?php

/*
 * @file  stock.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 存货模块测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/stock/stock
 */

require_once(ROOT_PATH . 'unit_test/helpers/environment.php');

echo "开始环境自动生成:Stock模块\n\n";

Environment::init_site();

$user1 = Environment::add_user('刘成');
$user2 = Environment::add_user('马睿');
$user3 = Environment::add_user('吴凯');
$user4 = Environment::add_user('吴天放');

$role1 = Environment::add_role('存货管理员', ['管理存货', '代人领用']);
$role2 = Environment::add_role('存货内容管理员', ['编辑存货内容']);
$role3 = Environment::add_role('存货关注管理员', ['查看其他用户关注的存货']);

Environment::set_role($user1, $role1);
Environment::set_role($user2, $role2);
Environment::set_role($user3, $role3);

echo "\n环境生成完毕\n";
