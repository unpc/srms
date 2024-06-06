<?php
/*
 * @file sample_reserv_calculute_fee.php
 * @author Jinlin.Li <jinlin.li@geneegroup.com>
 * @date 2013-01-29
 *
 * @brief 多财务模式下财务帐号充值、扣费、信用额度设定测试用例
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-sample/sample_reserv_calculute_fee
 */
if (!Module::is_installed('eq_charge') || !Module::is_installed('eq_sample') || !Module::is_installed('billing')) return true;
require_once ('sample_charge_test.php');
class sample_reserv_test extends sample_charge_test {
	function test_change_sender() {
		Unit_Test::echo_title('按时使用');
		$this->make_environment();
		$equipment = $this->equipment;
		$acct = $this->acct;
		$acct2 = $this->acct2;

		$user = $this->user;
		$user2 = $this->user2;
		$sample = self::mk_sample($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00', 1);
		Unit_Test::assert('账号1作为申请人创建送样预约 样品数 1', $sample->id > 0);
		
		$acct = ORM_Model::refetch($acct);
		$acct2 = ORM_Model::refetch($acct2);

		Unit_Test::assert('账号1余额 4990', $acct->balance == 4990);
		Unit_Test::assert('账号2余额 5000', $acct2->balance == 5000);
		
		$old_account = $sample->transaction->account;
		$old_sender_id = $sample->sender->id;
		$sample2 = self::mk_sample_change_sender($sample, $user2);
		Unit_Test::assert('修改账号2作为申请人', $old_sender_id !=  $sample2->sender->id);

		$acct = ORM_Model::refetch($acct);
		$acct2 = ORM_Model::refetch($acct2);

		Unit_Test::assert('账号1余额 5000', $acct->balance == 5000);
		Unit_Test::assert('账号2余额 4990', $acct2->balance == 4990);

	}
	function run()
	{
		$this->set_up();
		Unit_Test::echo_title('测试按样品数计费');
		$this->test_change_sender();

		$this->tear_down();
	}
}
$test = new sample_reserv_test;
$test->run();
