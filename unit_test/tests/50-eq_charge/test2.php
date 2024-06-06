<?php

/*
 * @file tests2.php
 * @author Guoping Zhang <guoping.zhang@geneegroup.com>
 * @date 2010-11-15
 *
 * @brief 测试预约使用 多段计费  超时计费 爽约计费，仪器使用收费方式发生改变，测试代码升级
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_charge/test2
 */
if (!Module::is_installed('eq_charge')) return true;
require_once(ROOT_PATH.'unit_test/helpers/environment.php');

//测试
Unit_Test::echo_title("准备环境");
	$backup_file = tempnam('/tmp', 'database');
	$db = Database::factory('ut');
	$ret = $db->snapshot($backup_file);
	Unit_Test::assert('数据库备份', $ret);

    Database::reset();

    ORM_Model::destroy('user');
    ORM_Model::destroy('lab');
    ORM_Model::destroy('equipment');
    ORM_Model::destroy('billing_department');
    ORM_Model::destroy('billing_account');
    ORM_Model::destroy('billing_transaction');
    ORM_Model::destroy('eq_record');
    ORM_Model::destroy('eq_charge');
    ORM_Model::destroy('calendar');
    ORM_Model::destroy('cal_component');
   	// 2016-1-26 unpc 清空数据库之后需要更新ORM-S以保证数据表的存在
    foreach(Config::$items['schema'] as $name=>$schema) {
		$db = Database::factory();
		$schema = ORM_Model::schema($name);
		if ($schema) {
			$ret = $db->prepare_table($name, $schema);
	        if (!$ret) {
	            echo $name."表更新失败\n";
	        }
		}
	}

	$lab1 = Environment::prepare_lab(1);
	$lab2 = Environment::prepare_lab(2);
	$lab3 = Environment::prepare_lab(3);
	
	$department = Environment::prepare_department(1);
	
	$account1 = Environment::prepare_account(1, $department, $lab1);
	$account2 = Environment::prepare_account(2, $department, $lab2);
	$account3 = Environment::prepare_account(3, $department, $lab3);
	
	$user1 = Environment::prepare_user(1, $lab1);
	$user2 = Environment::prepare_user(2, $lab2);
	$user3 = Environment::prepare_user(3, $lab3);
	
	$equipment = Environment::prepare_equipment(1);
	$equipment->billing_dept = $department;
	$equipment->control_mode = 'computer';
	$templates = Config::get('eq_charge.template');
	$template = $templates[time_reserv_record][content];
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
	$params['%template_title'] = $templates[time_reserv_record][title];
	$params['%script'] = '';
	$params['%template_type'] = 'time_reserv_record';

	$tstandard = EQ_Lua::get_lua_content('eq_charge', "private:reserv_template.lua");
	$tstandard = EQ_Charge_LUA::convert_script($tstandard, $params);
	$template_standard['reserv'] = $tstandard;

	$equipment->template_standard = $template_standard;
	$equipment->charge_script = $charge_script;
	$equipment->charge_template = ['reserv' => 'time_reserv_record'];	
	$equipment->save();

	$reserv_setting['*'] = [
		'unit_price' => max(round(50, 2), 0),
		'minimum_fee' => 0
	];
	$params = EQ_Lua::array_p2l($reserv_setting);
	EQ_Charge::update_charge_script($equipment, 'reserv', ['%options'=>$params]);
	EQ_Charge::put_charge_setting($equipment, 'reserv', $reserv_setting);

Unit_Test::echo_endl();

//测试
Unit_Test::echo_title("测试预约仪器代开");

	$equipment->accept_reserv = TRUE;
	$equipment->save();

	$dtstart = time() - 192800;
	$dtend = $dtstart + 3600 - 1;
	
	$calendar = O('calendar', ['parent'=>$equipment]);
	$calendar->parent = $equipment;
	$calendar->save();
	Environment::enlist($calendar);
	
	Unit_Test::echo_text('user1预约');
	$component = O('cal_component');
	$component->calendar = $calendar;
	$component->dtstart = $dtstart;
	$component->dtend = $dtend;
	$component->organizer = $user1;
	$component->save();
	Environment::enlist($component);
	$r = O('eq_reserv', ['component' => $component]);
	$charge = O('eq_charge', ['source' => $r]);
	if ($charge) Environment::enlist($charge);
	
	Unit_Test::assert('charge->id > 0', $charge->id > 0);
	Unit_Test::assert('charge->amount = 50', $charge->amount == 50);
	
	$account1 = ORM_Model::refetch($account1);
	Unit_Test::assert('lab1:account1->balance == -50', $account1->balance == -50);
	
	Unit_Test::echo_text('user1第一次使用');
	$record = O('eq_record');
	$record->equipment = $equipment;
	$record->user = $user1;
	$record->dtstart = $dtstart + 400;
	$record->dtend = $record->dtstart + 400 - 1;
	$record->reserv = $component;
	$record->save();
	
	$account1 = ORM_Model::refetch($account1);
	Unit_Test::assert('record->id > 0', $record->id > 0);
	Unit_Test::assert('lab1:account1->balance == -50', $account1->balance == -50, 'lab1:account1->balance = ' . $account1->balance);

	Unit_Test::echo_text('user1第二次使用');
	$record = O('eq_record');
	$record->equipment = $equipment;
	$record->user = $user1;
	$record->dtstart = $dtstart + 1200;
	$record->dtend = $record->dtstart + 400 - 1;
	$record->reserv = $component;
	$record->save();

	$account1 = ORM_Model::refetch($account1);
	Unit_Test::assert('record->id > 0', $record->id > 0);
	Unit_Test::assert('lab1:account1->balance == -50', $account1->balance == -50, 'lab1:account1->balance = ' . $account1->balance);

	Unit_Test::echo_text('user1第三次使用');
	$record = O('eq_record');
	$record->equipment = $equipment;
	$record->user = $user1;
	$record->dtstart = $dtstart + 1800;
	$record->dtend = $record->dtstart + 3600 - 1;
	$record->reserv = $component;
	$record->save();

	$account1 = ORM_Model::refetch($account1);
	Unit_Test::assert('record->id > 0', $record->id > 0);
	Unit_Test::assert('lab1:$account1->balance == -75', $account1->balance == -75, 'lab1:account1->balance = ' . $account1->balance);
	
	
	//第二个用户占用了开始
	Unit_Test::echo_text('user2占用部分时间');
	$record = O('eq_record');
	$record->equipment = $equipment;
	$record->user = $user2;
	$record->dtstart = $dtstart - 450;
	$record->dtend = $dtstart + 400 - 1;
	$record->save();

	$account1 = ORM_Model::refetch($account1);
	$account2= ORM_Model::refetch($account2);
	Unit_Test::assert('record->id > 0', $record->id > 0);
	Unit_Test::assert('lab1:account1->balance == -69.45', $account1->balance == -69.45, 'lab1:account1->balance = ' . $account1->balance);
	Unit_Test::assert('lab2:account2->balance == -11.81', $account2->balance == -11.81, 'lab2：account2->balance = ' . $account2->balance);
//用户爽约计费测试
Unit_Test::echo_title("测试爽约计费");
	
	$equipment->accept_reserv = TRUE;
	$equipment->save();

	$dtstart = time() - 86400;
	$dtend = $dtstart + 3600 - 1;
	
	$calendar = O('calendar', ['parent'=>$equipment]);
	$calendar->parent = $equipment;
	$calendar->save();
	Environment::enlist($calendar);
	
	Unit_Test::echo_text('user3预约');
	$component = O('cal_component');
	$component->calendar = $calendar;
	$component->dtstart = $dtstart;
	$component->dtend = $dtend;
	$component->organizer = $user3;
	$component->save();
	Environment::enlist($component);

	$r = O('eq_reserv', ['component' => $component]);
	$charge = O('eq_charge', ['source' => $r]);
	if ($charge) Environment::enlist($charge);
	
	Unit_Test::assert('charge->id > 0', $charge->id > 0);
	Unit_Test::assert('charge->amount = 50', $charge->amount == 50);
	
	$account3 = ORM_Model::refetch($account3);
	Unit_Test::assert('lab3:account3->balance == -50', $account3->balance == -50);

	$records = Q("eq_record[equipment=$equipment]");
	foreach ($records as $r) {
		$r->delete();
	}
Unit_Test::echo_endl();

Unit_Test::echo_title("撤销环境");
	Environment::destroy();
	$ret = $db->restore($backup_file, $restore_file);
	Unit_Test::assert('导入备份数据库', $ret);
Unit_Test::echo_endl();
