<?php
/*
 * @file uni_dept_transaction_and_credit_line_test.php
 * @author Rui Ma <rui.ma@geneegroup.com>
 * @date 2012-08-15
 *
 *
 * @brief 单财务模式下财务帐号充值、扣费、信用额度设定测试用例
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/billing/uni_dept_income_test
 */
if (!Module::is_installed('billing')) return true;
require 'uni_billing_dept.php';
require_once 'billing_helper.php';

Unit_Test::echo_title();
Unit_Test::echo_title('初始环境测试帐号总收入和余额');

$account1 = O('billing_account', 1);

Unit_Test::assert(strtr('测试获取 %account1 amount', ['%account1'=>$account1->lab->name]), Billing_Helper::get_amount($account1) == 5000, Billing_Helper::get_amount($account1));
Unit_Test::assert(strtr('测试获取 %account1 余额', ['%account1'=>$account1->lab->name]), Billing_Helper::get_balance($account1) == 4985);

Unit_Test::echo_title('执行充值操作');

Environment::add_income_transaction($account1, $user2, 300);

Unit_Test::echo_title('测试充值后(已确认状态)amount和余额');
Unit_Test::assert(strtr('测试获取 %account1 imcome', ['%account1'=>$account1->lab->name]), Billing_Helper::get_total_income($account1) == 5300);
Unit_Test::assert(strtr('测试获取 %account1 amount', ['%account1'=>$account1->lab->name]), Billing_Helper::get_amount($account1) == 5300);

Unit_Test::assert(strtr('测试获取 %account1 余额', ['%account1'=>$account1->lab->name]), Billing_Helper::get_balance($account1) == 5285);


Environment::add_pending_transaction($account1, $user2, 300);

Unit_Test::echo_title('测试充值后(未确认状态)amount和余额');
Unit_Test::assert(strtr('测试获取 %account1 imcome', ['%account1'=>$account1->lab->name]), Billing_Helper::get_total_income($account1) == 5600);
Unit_Test::assert(strtr('测试获取 %account1 amount', ['%account1'=>$account1->lab->name]), Billing_Helper::get_amount($account1) == 5300);

Unit_Test::assert(strtr('测试获取 %account1 余额', ['%account1'=>$account1->lab->name]), Billing_Helper::get_balance($account1) == 5285);

Unit_Test::echo_title('执行扣费操作');

Environment::add_outcome_transaction($account1, $user2, 600);

Unit_Test::echo_title('测试扣费后（已确认）amount、余额和总支出');
Unit_Test::assert(strtr('测试获取 %account1 imcome', ['%account1'=>$account1->lab->name]), Billing_Helper::get_total_income($account1) == 5600);
Unit_Test::assert(strtr('测试获取 %account1 amount', ['%account1'=>$account1->lab->name]), Billing_Helper::get_amount($account1) == 5300);

Unit_Test::assert(strtr('测试获取 %account1 余额', ['%account1'=>$account1->lab->name]), Billing_Helper::get_balance($account1) == 4685);

Unit_Test::assert(strtr('测试获取 %account1 总支出', ['%account1'=>$account1->lab->name]), Billing_Helper::get_outcome($account1) == 615);


Environment::add_pending_outcome_transaction($account1, $user2, 600);

Unit_Test::echo_title('测试扣费后（未确认）amount、余额和总支出');
Unit_Test::assert(strtr('测试获取 %account1 imcome', ['%account1'=>$account1->lab->name]), Billing_Helper::get_total_income($account1) == 5600);
Unit_Test::assert(strtr('测试获取 %account1 amount', ['%account1'=>$account1->lab->name]), Billing_Helper::get_amount($account1) == 5300);

Unit_Test::assert(strtr('测试获取 %account1 余额', ['%account1'=>$account1->lab->name]), Billing_Helper::get_balance($account1) == 4085);

Unit_Test::assert(strtr('测试获取 %account1 总支出', ['%account1'=>$account1->lab->name]), Billing_Helper::get_outcome($account1) == 1215);


Unit_Test::echo_title('执行信用额度设定操作');

Environment::set_account_credit_line($account1, '300');

Unit_Test::echo_title('测试设定信用额度后amount、余额和总支出');
Unit_Test::assert(strtr('测试获取 %account1 imcome', ['%account1'=>$account1->lab->name]), Billing_Helper::get_total_income($account1) == 5600);
Unit_Test::assert(strtr('测试获取 %account1 amount', ['%account1'=>$account1->lab->name]), Billing_Helper::get_amount($account1) == 5300);

Unit_Test::assert(strtr('测试获取 %account1  余额', ['%account1'=>$account1->lab->name]), Billing_Helper::get_balance($account1) == 4085);

Unit_Test::assert(strtr('测试获取 %account1  总支出', ['%account1'=>$account1->lab->name]), Billing_Helper::get_outcome($account1) == 1215);
