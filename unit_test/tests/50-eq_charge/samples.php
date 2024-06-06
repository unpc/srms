<?php
/*
 * @file samples.php
 * @author Xiaopei Li <xiaopei.li@geneegroup.com>
 * @date 2011-07-26
 *
 * @brief 设定仪器计费按照送样数进行计费的测试用例
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_charge/samples
 */
if (!Module::is_installed('eq_charge') || !Module::is_installed('eq_sample') || !Module::is_installed('billing')) return true;
require_once ('charge_test.php');
class samples_charge_test_1 extends charge_test
{
	function set_up_equipment($equipment)
	{
		$equipment->charge_mode = EQ_Charge::CHARGE_MODE_SAMPLES;
		$equipment->unit_price = 600;
		return $equipment;
	}
	/* 测试各种使用情况下的费用 */
	function test_no_reserv() {
		/* 	a. 无预约 */
		Unit_Test::echo_title('无预约，1 样品');
		$this->make_environment();
		$record = self::mk_record($this->equipment, $this->user, '2011/04/01 19:00', '2011/04/01 20:00', 1); /* 使用 1 小时，1 样品 */
		Unit_Test::assert('无预约，使用 1 小时、 1 样品', $record->id);
		$this->assert_balance('共 1 样品', 5000 - 600);
		
	}
	function test_two_samples() {
		/* 	b. 无预约 */
		Unit_Test::echo_title('无预约，2 样品');
		$this->make_environment();
		$record = self::mk_record($this->equipment, $this->user, '2011/04/01 19:00', '2011/04/01 20:00', 2); /* 使用 1 小时，2 样品 */
		Unit_Test::assert('无预约，使用 1 小时、 2 样品', $record->id);
		$this->assert_balance('共 2 样品', 5000 - 1200);

	}
	function test_on_time() {
		/* 	c. 预约，按时使用，1样品 */
		Unit_Test::echo_title('按时使用');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id > 0);
		$record = self::mk_record($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('在预约时段中使用', $record->id > 0);
		$this->assert_balance('花费 600', 5000 - 600);

	}
	function test_on_time_many_records() {
		/* d. 按时使用，但在预约内使用 2 次 */
		Unit_Test::echo_title('按时间断使用');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id > 0);
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 19:00', '2011/04/01 19:40');
		Unit_Test::assert('在预约时段内，先使用了40分钟...', $record_1->id > 0);
		$record_2 = self::mk_record($equipment, $user, '2011/04/01 19:45', '2011/04/01 19:55');
		Unit_Test::assert('又使用了 10 分钟...', $record_2->id > 0);
		$this->assert_balance('花费 600', 5000 - 1200);

	}
	function test_not_use() {
		/* e. 爽约 */
		Unit_Test::echo_title('爽约');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约了 1 小时', $reserv->id);
		/* 未使用 */
		$this->assert_balance('未使用，默认 1 样品', 5000 - 600);

	}
	function run()
	{
		$this->set_up();
		Unit_Test::echo_title('测试按样品数计费');
		$this->test_no_reserv();
		$this->test_two_samples();
		$this->test_on_time();
		$this->test_on_time_many_records();
		$this->test_not_use();

		$this->tear_down();
	}
}
$test = new samples_charge_test_1;
$test->run();
