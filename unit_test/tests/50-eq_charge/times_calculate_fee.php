<?php
/*
 * @file times_calculate_fee.php
 * @author Xiaopei Li <xiaopei.li@geneegroup.com>
 * @date 2011-07-26
 *
 * @brief 设定仪器计费按照使用次数进行计费的测试用例
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_charge/times_calculate_fee
 */
if (!Module::is_installed('eq_charge')) return true;
require_once ('charge_test.php');
class times_charge_test_2 extends charge_test
{
	function set_up_equipment($equipment)
	{
		$equipment->charge_mode = EQ_Charge::CHARGE_MODE_TIMES;
		$equipment->unit_price = 600;
		return $equipment;
	}
	/* 测试各种使用情况下的费用 */
	function test_no_reserv() {
		/* 	a. 无预约 */
		Unit_Test::echo_title('无预约');
		$this->make_environment();
		$record = self::mk_record_and_assert_auto_amount_before_save(600, $this->equipment, $this->user, '2011/04/01 19:00', '2011/04/01 20:00'); /* 使用 1 小时 */
		Unit_Test::assert('无预约，使用 1 小时', $record->id);
		$this->assert_balance('共使用 1 次，花费 600', 5000 - 600);
		
	}
	function test_on_time() {
		/* 	b. 按时使用 */
		Unit_Test::echo_title('按时使用');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id > 0);
		$record = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('在预约时段中使用', $record->id > 0);
		$this->assert_balance('共使用 1 次，花费 600', 5000 - 600);
		
	}
	function test_on_time_many_records() {
		/* c. 按时间断使用 */
		Unit_Test::echo_title('按时间断使用');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id > 0);
		$record_1 = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 19:00', '2011/04/01 19:40');
		Unit_Test::assert('在预约时段内，先使用了40分钟...', $record_1->id > 0);
		$record_2 = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 19:45', '2011/04/01 19:55');
		Unit_Test::assert('又使用了 10 分钟...', $record_2->id > 0);
		$this->assert_balance('共使用 2 次，花费 1200', 5000 - 1200);
		
	}
	function test_over_time() {
		/* 	d. 超时使用 */
		Unit_Test::echo_title('超时使用');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id);
		$record = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 19:00', '2011/04/01 21:00');
		Unit_Test::assert('超时使用了 1 小时', $record->id);
		$this->assert_balance('共使用 1 次，花费 600', 5000 - 600);
		
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
		$this->assert_balance('共使用 1 次，花费 600', 5000 - 600);
		
	}
	function test_the_others_over_time() {
		/* 	f. 被他人占用时间 */
		Unit_Test::echo_title('被他人占用时间');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id);
		$record_1 = self::mk_record_and_assert_auto_amount_before_save(0, $equipment, $equipment->contact, '2011/04/01 18:30', '2011/04/01 19:30');
		Unit_Test::assert('但开始使用前被别人占用了半小时', $record_1->id);
		$record_2 = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 19:35', '2011/04/01 19:55');
		Unit_Test::assert('自己仅使用了 20 分钟', $record_2->id);
		$this->assert_balance('共使用 1 次，花费 600', 5000 - 600);
		
	}
	function test_the_others_uses_in_reserv() {
		/* g. 使用期间被管理员占用时间 */
		Unit_Test::echo_title('使用期间被管理员占用时间');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id);
		$record_1 = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 19:00', '2011/04/01 19:10');
		Unit_Test::assert('使用了 10 分钟', $record_1->id);
		$record_2 = self::mk_record_and_assert_auto_amount_before_save(0, $equipment, $equipment->contact, '2011/04/01 19:11',  '2011/04/01 19:20');
		Unit_Test::assert('被占用了 10 分钟', $record_2->id);
		$record_3 = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 19:25', '2011/04/01 20:00');
		Unit_Test::assert('接着使用完毕', $record_3->id);
		$this->assert_balance('共使用 2 次，花费 1200', 5000 - 1200);
		
	}
	function test_one_overtime_and_one_not_user() {
		/* 	h. 一次超时，一次爽约 */
		Unit_Test::echo_title('一次超时，一次爽约');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv_1 = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv_1->id);
		$reserv_2 = self::mk_reserv($equipment, $user, '2011/04/01 20:30', '2011/04/01 21:00');
		Unit_Test::assert('在第一条预约半小时后，又预约了半小时', $reserv_2->id);
		$record = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 19:30', '2011/04/01 20:45');
		Unit_Test::assert('在第一条预约中开始使用，使用到第二条预约中', $record->id);
		$this->assert_balance('共使用 2 次，花费 1200', 5000 - 1200);
		
	}
	function test_two_reserv_and_three_record() {
		/* 	h. 一次超时 */
		Unit_Test::echo_title('一次超时');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		/* 2条预约 */
		$reserv_1 = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv_1->id);
		$reserv_2 = self::mk_reserv($equipment, $user, '2011/04/01 20:30', '2011/04/01 21:00');
		Unit_Test::assert('在第一条预约半小时后，又预约了半小时', $reserv_2->id);
		/* 3条使用 */
		$record_1 = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 19:05', '2011/04/01 19:30');
		Unit_Test::assert('在第一条预约中使用', $record_1->id);
		$record_2 = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 19:50', '2011/04/01 20:45');
		Unit_Test::assert('在第一条预约中开始使用，使用到第二条预约中', $record_2->id);
		$record_3 = self::mk_record_and_assert_auto_amount_before_save(600, $equipment, $user, '2011/04/01 20:50', '2011/04/01 20:55');
		Unit_Test::assert('在第二条预约中使用', $record_3->id);
		$this->assert_balance('共使用 3 次，花费 1800', 5000 - 1800);
		
	}
	function run()
	{
		$this->set_up();

		Unit_Test::echo_title('测试按使用次数计费');
		/*
		  测试各种使用方式下的计费(参考文档:收费结算方式):
		  a. 无预约
		  b. 按时使用
		  c. 按时间断使用
		  d. 超时使用
		  e. 爽约
		  f. 被他人占用时间
		  g. 使用期间被管理员占用时间
		  h. 一次超时，一次爽约
		  i. 一次超时
		*/
		$this->test_no_reserv();
		$this->test_on_time();
		$this->test_on_time_many_records();
		$this->test_over_time();
		$this->test_not_use();
		$this->test_the_others_over_time();
		$this->test_the_others_uses_in_reserv();
		$this->test_one_overtime_and_one_not_user();
		$this->test_two_reserv_and_three_record();

		
	}
}
$test = new times_charge_test_2;
$test->run();
