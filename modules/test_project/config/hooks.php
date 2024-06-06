<?php

$config['controller[!equipments/equipment/edit].ready'][] = 'Test_Project::setup_edit';

$config['extra.charge.setting.view'][] = ['callback' => 'Test_Project::extra_charge_setting_view', 'weight' => 95];
$config['extra.charge.setting.content'][] = 'Test_Project::extra_charge_setting_content';
$config['equipment.charge.edit.content.tabs'][] = 'Test_Project::charge_edit_content_tabs';
$config['template[test_project_count].setting_view'][] = 'Test_Project::template_test_project_count_setting_view';
$config['equipment_model.call.get_test_project_items'][] = 'Test_Project::get_test_project_items';

// 预约界面增加视图 & 表单验证 & 保存
$config['eq_reserv.prerender.component'][] = 'Test_Project_Reserv::eq_reserv_prerender_component';

// 送样添加/编辑页面增加视图 & 表单验证 & 保存
$config['eq_sample.prerender.add.form'][] = 'Test_Project_Sample::eq_sample_prerender_add_form';
$config['eq_sample.prerender.edit.form'][] = 'Test_Project_Sample::eq_sample_prerender_edit_form';
$config['extra.form.validate'][] = 'Test_Project::extra_form_validate';
$config['sample.form.submit'][] = 'Test_Project_Sample::eq_sample_form_submit';

//送样查看页
$config['sample.extra.print'][] = 'Test_Project_Sample::sample_extra_print';

//收费
$config['eq_charge.lua_cal_ext_amount'][] = 'Test_Project_Charge::lua_cal_ext_amount';
$config['charge_lua_result.after.calculate_amount'][] = 'Test_Project_Charge::after_calculate_amount';
$config['eq_sample.has_relative_charge'][] = 'Test_Project_Charge::has_relative_charge';
$config['eq_reserv.has_relative_charge'][] = 'Test_Project_Charge::has_relative_charge';

$config['eq_charge.sample_render'][] = 'Test_Project_Sample::eq_sample_form_submit';