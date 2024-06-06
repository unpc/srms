<?php
/*
 * @file multi_billing_dept.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 多财务模式下财务模块测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/billing/multi_billing_dept
 */


if (!Module::is_installed('billing')) return true;

require 'base.php';
$GLOBALS['preload']['billing.single_department'] = FALSE;

$department1 = Environment::add_department('南开大学财务处');
$department2 = Environment::add_department('北京大学财务处');

$account1 = Environment::add_account($lab1, $department1);
$account2 = Environment::add_account($lab1, $department2);

Environment::add_transaction($account1, $user2, 5000);
Environment::add_transaction($account2, $user2, 3000);
