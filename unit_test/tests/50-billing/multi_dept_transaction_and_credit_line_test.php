<?php
/*
 * @file multi_dept_transaction_and_credit_line_test.php
 * @author Rui Ma <rui.ma@geneegroup.com>
 * @date 2012-08-15
 *
 * @brief 多财务模式下财务帐号充值、扣费、信用额度设定测试用例
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/billing/multi_dept_transaction_and_credit_line_test.php
 */

if (!Module::is_installed('billing')) return true;

require 'multi_billing_dept.php';
require_once 'billing_helper.php';

Unit_Test::echo_title();
Unit_Test::echo_title('初始环境测试帐号 amount和余额');

Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_total_income($account1) == 5000);
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_amount($account1) == 5000);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_total_income($account2) == 3000);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_amount($account2) == 3000);

Unit_Test::assert(strtr('测试获取 %account1 在%department1 余额', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_balance($account1) == 5000);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 余额', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_balance($account2) == 3000);

Unit_Test::echo_title('执行充值操作');

Environment::add_income_transaction($account1, $user2, 300);
Environment::add_income_transaction($account2, $user2, 700);

Unit_Test::echo_title('测试充值后 amount和余额');
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_total_income($account1) == 5300);
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_amount($account1) == 5300);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_total_income($account2) == 3700);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_amount($account2) == 3700);

Unit_Test::assert(strtr('测试获取 %account1 在%department1 余额', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_balance($account1) == 5300);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 余额', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_balance($account2) == 3700);



Environment::add_pending_transaction($account1, $user2, 300);
Environment::add_pending_transaction($account2, $user2, 700);

Unit_Test::echo_title('测试充值后 amount和余额');
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_total_income($account1) == 5600);
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_amount($account1) == 5300);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_total_income($account2) == 4400);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_amount($account2) == 3700);

Unit_Test::assert(strtr('测试获取 %account1 在%department1 余额', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_balance($account1) == 5300);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 余额', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_balance($account2) == 3700);


Unit_Test::echo_title('执行扣费操作');

Environment::add_outcome_transaction($account1, $user2, 600);
Environment::add_outcome_transaction($account2, $user2, 200);

Unit_Test::echo_title('测试扣费后 amount、余额和总支出');
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_total_income($account1) == 5600);
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_amount($account1) == 5300);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_total_income($account2) == 4400);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_amount($account2) == 3700);

Unit_Test::assert(strtr('测试获取 %account1 在%department1 余额', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_balance($account1) == 4700);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 余额', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_balance($account2) == 3500);

Unit_Test::assert(strtr('测试获取 %account1 在%department1 总支出', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_outcome($account1) == 600);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 总支出', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_outcome($account2) == 200);


Unit_Test::echo_title('执行扣费操作');

Environment::add_pending_outcome_transaction($account1, $user2, 600);
Environment::add_pending_outcome_transaction($account2, $user2, 200);

Unit_Test::echo_title('测试扣费后 amount、余额和总支出');
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_total_income($account1) == 5600);
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_amount($account1) == 5300);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_total_income($account2) == 4400);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_amount($account2) == 3700);

Unit_Test::assert(strtr('测试获取 %account1 在%department1 余额', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_balance($account1) == 4100);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 余额', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_balance($account2) == 3300);

Unit_Test::assert(strtr('测试获取 %account1 在%department1 总支出', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_outcome($account1) == 1200);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 总支出', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_outcome($account2) == 400);


Unit_Test::echo_title('执行信用额度设定操作');

Environment::set_account_credit_line($account1, '300');
Environment::set_account_credit_line($account2, '-5000');

Unit_Test::echo_title('测试设定信用额度后 amount、余额和总支出');
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_total_income($account1) == 5600);
Unit_Test::assert(strtr('测试获取 %account1 在%department1 amount', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_amount($account1) == 5300);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_total_income($account2) == 4400);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 amount', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_amount($account2) == 3700);

Unit_Test::assert(strtr('测试获取 %account1 在%department1 余额', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_balance($account1) == 4100);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 余额', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_balance($account2) == 3300);

Unit_Test::assert(strtr('测试获取 %account1 在%department1 总支出', ['%account1'=>$account1->lab->name, '%department1'=>$department1->name]), Billing_Helper::get_outcome($account1) == 1200);
Unit_Test::assert(strtr('测试获取 %account2 在%department2 总支出', ['%account2'=>$account2->lab->name, '%department2'=>$department2->name]), Billing_Helper::get_outcome($account2) == 400);
