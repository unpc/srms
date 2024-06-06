<?php
/*
 * @file free.php
 * @author Xiaopei Li <xiaopei.li@geneegroup.com>
 * @date 2011-07-26
 *
 * @brief 设定仪器计费免费使用进行计费的测试用例
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_charge/free
 */
if (!Module::is_installed('eq_charge')) return true;
require_once ('charge_test.php');
class free_charge_test_1 extends charge_test
{
	function set_up_equipment($equipment)
	{
		$equipment->charge_mode = EQ_Charge::CHARGE_MODE_FREE;
		return $equipment;
	}
	/* 测试各种使用情况下的费用 */
	function test_no_reserv() {
		/* 	a. 无预约 */
		Unit_Test::echo_title('无预约');
		$this->make_environment();
		$record = self::mk_record($this->equipment, $this->user, '2011/04/01 19:00', '2011/04/01 20:00'); /* 使用 1 小时 */
		Unit_Test::assert('无预约，使用 1 小时', $record->id);
		$this->assert_balance('对账，仍为5000', 5000);

	}
	function test_on_time() {
		/* 	b. 按时使用 */
		Unit_Test::echo_title('按时使用');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id > 0);
		$record = self::mk_record($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('在预约时段中使用', $record->id > 0);
		$this->assert_balance('对账，仍为5000', 5000);

	}
	function test_not_use() {
		/* 	e. 爽约 */
		Unit_Test::echo_title('爽约');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约了 1 小时', $reserv->id);
		/* 未使用 */
		$this->assert_balance('对账，仍为5000', 5000);
		
	}
	function run()
	{
		$this->set_up();
		
		Unit_Test::echo_title('测试免费使用');
		$this->test_no_reserv();
		$this->test_on_time();
		$this->test_not_use();

		$this->tear_down();
	}
}
$test = new free_charge_test_1;
$test->run();
