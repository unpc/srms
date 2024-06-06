<?php
/*
 * @file duration.php
 * @author Xiaopei Li <xiaopei.li@geneegroup.com>
 * @date 2011-07-26
 *
     * @brief 设定仪器计费按照使用时长进行计费的测试用例
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_charge/duration
 */
if (!Module::is_installed('eq_charge') || !Module::is_installed('billing')) return true;
require_once ('charge_test.php');
class duration_charge_test_1 extends charge_test
{
	function set_up_equipment($equipment)
	{
		$templates = Config::get('eq_charge.template');
		$template = $templates[record_time][content];
		$template_standard = [];
		foreach ($template as $k => $v) {
			$convert_script = NULL;
			$script = EQ_Lua::get_lua_content('eq_charge', $v['script']);

			foreach ((array)$v['params'] as $key => $value) {
				$params[$key] = EQ_Lua::array_p2l($value) ?: $value;
			}
			$convert_script = EQ_Charge_LUA::convert_script($script, $params);
			$charge_script[$k] = $convert_script;
			
		}

		$params['%template_title'] = $templates[record_time][title];
		$params['%script'] = '';
		$params['%template_type'] = 'record_time';

		$tstandard = EQ_Lua::get_lua_content('eq_charge', "private:record_template.lua");
		$tstandard = EQ_Charge_LUA::convert_script($tstandard, $params);
		$template_standard['record'] = $tstandard;

		$equipment->template_standard = $template_standard;
		$equipment->charge_script = $charge_script;
		$equipment->charge_template = ['record' => 'record_time'];
		return $equipment;
	}

	function set_up_charge($equipment) 
	{
		$record_setting['*'] = [
			'unit_price' => 600,
			'minimum_fee' => 0
		];
		$params = EQ_Lua::array_p2l($record_setting);
		EQ_Charge::update_charge_script($equipment, 'record', ['%options'=>$params]);
		EQ_Charge::put_charge_setting($equipment, 'record', $record_setting);

		return $equipment;
	}

	/* 测试各种使用情况下的费用 */
	function test_no_reserv() {
		/* 	a. 无预约 */
		Unit_Test::echo_title('无预约');
		$this->make_environment();
		$record = self::mk_record($this->equipment, $this->user, '2011/04/01 19:00', '2011/04/01 20:00'); /* 使用 1 小时 */
		Unit_Test::assert('无预约，使用 1 小时', $record->id);
		$this->assert_balance('花费 600', 5000 - 600);

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
		$this->assert_balance('花费 600', 5000 - 600);

	}
	function test_on_time_many_records() {
		/* c. 按时间断使用 */
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
		$this->assert_balance('花费 500', 5000 - 500);

	}
	function test_over_time() {
		/* 	d. 超时使用 */
		Unit_Test::echo_title('超时使用');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id);
		$record = self::mk_record($equipment, $user, '2011/04/01 19:00', '2011/04/01 21:00');
		Unit_Test::assert('超时使用了 1 小时', $record->id);
		$this->assert_balance('花费 1200', 5000 - 1200);

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
		$this->assert_balance('花费 0', 5000);

	}
	function test_the_others_over_time() {
		/* 	f. 被他人(管理员)占用时间 */
		Unit_Test::echo_title('被他人占用时间');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id);
		$record_1 = self::mk_record($equipment, $equipment->contact, '2011/04/01 18:30', '2011/04/01 19:30');
		Unit_Test::assert('但开始使用前被别人(管理员)占用了半小时', $record_1->id);
		$record_2 = self::mk_record($equipment, $user, '2011/04/01 19:35', '2011/04/01 19:55');
		Unit_Test::assert('自己仅使用了 20 分钟', $record_2->id);
		$this->assert_balance('花费 200', 5000 - 200);

	}
	function test_the_others_uses_in_reserv() {
		/* g. 使用期间被管理员占用时间 */
		Unit_Test::echo_title('使用期间被管理员占用时间');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$reserv = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv->id);
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 19:00', '2011/04/01 19:10');
		Unit_Test::assert('使用了 10 分钟', $record_1->id);
		$record_2 = self::mk_record($equipment, $equipment->contact, '2011/04/01 19:11',  '2011/04/01 19:20');
		Unit_Test::assert('被占用了 10 分钟', $record_2->id);
		$record_3 = self::mk_record($equipment, $user, '2011/04/01 19:25', '2011/04/01 20:00');
		Unit_Test::assert('接着使用完毕', $record_3->id);
		$this->assert_balance('花费 450，需管理员过后修改金额', 5000 - 450);

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
		$record = self::mk_record($equipment, $user, '2011/04/01 19:30', '2011/04/01 20:45');
		Unit_Test::assert('在第一条预约中开始使用，使用到第二条预约中', $record->id);
		$this->assert_balance('花费 750', 5000 - 750);

	}
	function test_two_reserv_and_three_record() {
		/* 	i. 一次超时 */
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
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 19:05', '2011/04/01 19:30');
		Unit_Test::assert('在第一条预约中使用', $record_1->id);
		$record_2 = self::mk_record($equipment, $user, '2011/04/01 19:50', '2011/04/01 20:45');
		Unit_Test::assert('在第一条预约中开始使用，使用到第二条预约中', $record_2->id);
		$record_3 = self::mk_record($equipment, $user, '2011/04/01 20:50', '2011/04/01 20:55');
		Unit_Test::assert('在第二条预约中使用', $record_3->id);
		$this->assert_balance('2条预约，3次使用，花费 250 + 550 + 50 ', 5000 - 850);
		
	}
	function test_two_reserv_and_the_contact_use() {
		/* 	j 管理员占用 */
		Unit_Test::echo_title('两条预约，管理员从第一条的末尾占用到第二条开始');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$user2 = $this->user2;
		/* 2条预约 */
		$reserv_1 = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv_1->id);
		$reserv_2 = self::mk_reserv($equipment, $user2, '2011/04/01 20:30', '2011/04/01 21:00');
		Unit_Test::assert('在第一条预约半小时后，用户2又预约了半小时', $reserv_2->id);
		/* 3条使用 */
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 19:05', '2011/04/01 19:30');
		Unit_Test::assert('在第一条预约中使用', $record_1->id);
		$record_2 = self::mk_record($equipment, $equipment->contact, '2011/04/01 19:50', '2011/04/01 20:45');
		Unit_Test::assert('仪器负责人在第一条预约中开始使用，使用到第二条预约中', $record_2->id);
		$record_3 = self::mk_record($equipment, $user2, '2011/04/01 20:50', '2011/04/01 21:10');
		Unit_Test::assert('在第二条预约中使用,并且超时10分钟', $record_3->id);
		$this->assert_balance('2条预约，3次使用，花费 450', 5000 - 450);
	}
	function test_use_before_reserv() {
		/* 	k 提前开始使用 */
		Unit_Test::echo_title('提前开始使用');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		/* 1条预约 */
		$reserv_1 = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv_1->id);
		/* 1条使用 */
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 18:50', '2011/04/01 19:40');
		Unit_Test::assert('提前使用', $record_1->id);
		$this->assert_balance('1条预约，1次使用，花费 500', 5000 - 500);
	}
	function test_use_before_reserv_and_overtime() {
		/* 	l 提前开始使用且超时 */
		Unit_Test::echo_title('提前开始使用且超时');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		/* 1条预约 */
		$reserv_1 = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv_1->id);
		/* 1条使用 */
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 18:50', '2011/04/01 20:10');
		Unit_Test::assert('提前使用', $record_1->id);
		$this->assert_balance('1条预约，1次使用，花费 800', 5000 - 800);
	}
	function test_use_overtime_to_next_self_reserv() {
		/* 	m 超时使用致下一个自己的预约 */
		Unit_Test::echo_title('超时使用致下一个自己的预约');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		/* 1条预约 */
		$reserv_1 = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv_1->id);
		$reserv_2 = self::mk_reserv($equipment, $user, '2011/04/01 20:30', '2011/04/01 21:00');
		Unit_Test::assert('预约 1 小时', $reserv_2->id);
		/* 1条使用 */
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 19:50', '2011/04/01 20:40');
		Unit_Test::assert('在第一条预约中使用', $record_1->id);
		$this->assert_balance('2条预约，1次使用，花费 500', 5000 - 500);
	}
	function test_use_from_other_reserv_to_self_reserv() {
		/* 	n 从他人预约超时使用致自己的预约 */
		Unit_Test::echo_title('从他人预约超时使用致自己的预约');
		$this->make_environment();
		$equipment = $this->equipment;
		$user = $this->user;
		$user2 = $this->user2;
		/* 2条预约 */
		$reserv_1 = self::mk_reserv($equipment, $user, '2011/04/01 19:00', '2011/04/01 20:00');
		Unit_Test::assert('预约 1 小时', $reserv_1->id);
		$reserv_2 = self::mk_reserv($equipment, $user2, '2011/04/01 20:30', '2011/04/01 21:00');
		Unit_Test::assert('预约 1 小时', $reserv_2->id);
		/* 2条使用 */
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 19:10', '2011/04/01 19:40');
		Unit_Test::assert('在第一条预约中使用', $record_1->id);
		$record_2 = self::mk_record($equipment, $user2, '2011/04/01 19:50', '2011/04/01 20:40');
		Unit_Test::assert('在第一条预约中使用', $record_2->id);
		$this->assert_balance('2条预约，2次使用，花费 800', 5000 - 800);
	}
	function run()
	{
		$this->set_up();

		Unit_Test::echo_title('测试按使用时间计费');
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
		  j. 管理员占用
		  k. 提前开始使用
		  l. 提前开始使用且超时
		  m. 超时使用致下一个自己的预约
		  n. 从他人预约超时使用致自己的预约
		*/
		$this->test_two_reserv_and_the_contact_use();
		$this->test_use_from_other_reserv_to_self_reserv();
		$this->test_two_reserv_and_three_record();
		$this->test_no_reserv();
		$this->test_on_time();
		$this->test_on_time_many_records();
		$this->test_over_time();
		$this->test_not_use();
		$this->test_the_others_over_time();
		$this->test_the_others_uses_in_reserv();
		$this->test_one_overtime_and_one_not_user();
		$this->test_use_before_reserv();
		$this->test_use_before_reserv_and_overtime();
		$this->test_use_overtime_to_next_self_reserv();

		$this->tear_down();
	}
}
$test = new duration_charge_test_1;
$test->run();
