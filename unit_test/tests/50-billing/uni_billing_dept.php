<?php
/*
 * @file uni_billing_dept.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief  单一财务部门测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/billing/uni_billing_dept
 */

if (!Module::is_installed('billing')) return true;


require 'base.php';

$GLOBALS['preload']['billing.single_department'] = TRUE;

$department = Billing_Department::get();
$user4->connect($department);

$account1 = Environment::add_account($lab1, $department);
$transaction1 = Environment::add_transaction($account1, $user4, 5000);

$equipment1 = Environment::add_equipment('表面形貌测量仪', [$user4], [$user4]);

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

	$params['%template_title'] = $templates[record_time][title];
	$params['%script'] = '';
	$params['%template_type'] = 'record_time';

	$tstandard = EQ_Lua::get_lua_content('eq_charge', "private:record_template.lua");
	$tstandard = EQ_Charge_LUA::convert_script($tstandard, $params);
	$template_standard['record'] = $tstandard;
}

$equipment1->template_standard = $template_standard;
$equipment1->charge_script = $charge_script;
$equipment1->charge_template = ['record' => 'record_time'];
Unit_Test::assert(strtr("更新仪器: [%name]的计费设置为按时间收费!", ['%name' => $equipment1->name]), $equipment1->save());

$record_setting['*'] = [
	'unit_price' => max(round(15, 2), 0),
	'minimum_fee' => 0
];
$params = EQ_Lua::array_p2l($record_setting);
EQ_Charge::update_charge_script($equipment1, 'record', ['%options'=>$params]);
EQ_Charge::put_charge_setting($equipment1, 'record', $record_setting);

$eq_record = O('eq_record');
$eq_record->equipment = $equipment1;
$eq_record->user = $user2;
$eq_record->dtend = time();
$eq_record->dtstart = $eq_record->dtend - 3599;
$eq_record->samples = 1;
$eq_record->not_overtime = 1;
$eq_record->save();
