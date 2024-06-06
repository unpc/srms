<?php
/*
 * @file glogon_duration.php
 * @author Yu Li <yu.li@geneegroup.com>
 * @date 2013-02-19
 *
 * @brief 模拟glogon登录，如果提前使用时产生计费的测试
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_charge/glogon_duration
 */
if (!Module::is_installed('eq_charge') || !Module::is_installed('billing')) return true;

require_once (ROOT_PATH. 'unit_test/helpers/environment.php');
Environment::init_site();

require_once ('charge_test.php');

class glogon_duration_charge_test extends charge_test
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
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 19:05', '0');
		$record_1->dtend = strtotime('2011/04/01 19:30') - 1;
		$record_1->save();
		Unit_Test::assert('在第一条预约中使用', $record_1->id);
		$record_2 = self::mk_record($equipment, $equipment->contact, '2011/04/01 19:50', '0');
		$record_2->dtend = strtotime('2011/04/01 20:45') - 1;
		$record_2->save();
		Unit_Test::assert('仪器负责人在第一条预约中开始使用，使用到第二条预约中', $record_2->id);
		$record_3 = self::mk_record($equipment, $user2, '2011/04/01 20:50', '0');
		$record_3->dtend = strtotime('2011/04/01 21:10') - 1;
		$record_3->save();
		Unit_Test::assert('在第二条预约中使用,并且超时10分钟', $record_3->id);
		$this->assert_balance('2条预约，3次使用，花费 450', 5000 - 250 - 200);
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
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 18:50', '0');
		$record_1->dtend = strtotime('2011/04/01 19:40') - 1;
		$record_1->save();
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
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 18:50:00', '0');
		$record_1->dtend = strtotime('2011/04/01 20:10:00') - 1;
		$record_1->save();
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
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 19:50:00', 0);
		$record_1->dtend = strtotime('2011/04/01 20:40:00') - 1;
		$record_1->save();
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
		$record_1 = self::mk_record($equipment, $user, '2011/04/01 19:10:00', '0');
		$record_1->dtend = strtotime('2011/04/01 19:40:00') - 1;
		$record_1->save();
		Unit_Test::assert('在第一条预约中使用', $record_1->id);
		$record_2 = self::mk_record($equipment, $user2, '2011/04/01 19:50:00', '0');
		$record_2->dtend = strtotime('2011/04/01 20:40:00') - 1;
		$record_2->save();
		Unit_Test::assert('在第一条预约中使用', $record_2->id);
		$this->assert_balance('2条预约，2次使用，花费 800', 5000 - 300 - 500);
	}
	function run()
	{
		$this->set_up();

		Unit_Test::echo_title('测试按使用时间计费');
		/*
		  测试各种使用方式下的计费(参考文档:收费结算方式):
		  j. 管理员占用
		  k. 提前开始使用
		  l. 提前开始使用且超时
		  m. 超时使用致下一个自己的预约
		  n. 从他人预约超时使用致自己的预约
		*/
		$this->test_two_reserv_and_the_contact_use();
		$this->test_use_before_reserv_and_overtime();
        $this->test_use_from_other_reserv_to_self_reserv();
		$this->test_use_before_reserv();
		$this->test_use_overtime_to_next_self_reserv();

		$this->tear_down();
	}
}
$test = new glogon_duration_charge_test;
$test->run();
