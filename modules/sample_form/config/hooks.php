<?php

$config['controller[!equipments/equipment/edit].ready'][] = 'Sample_Form::setup_edit';

$config['extra.charge.setting.view'][] = 'Sample_Form::extra_charge_setting_view';
$config['extra.charge.setting.content'][] = 'Sample_Form::extra_charge_setting_content';

$config['equipment.charge.edit.content.tabs'][] = 'Sample_Form::charge_edit_content_tabs';

$config['eq_element_model.saved'][] = 'Sample_Form::on_eq_element_saved';
$config['eq_element_model.deleted'][] = 'Sample_Form::on_eq_element_deleted';

// 仪器负责人可以上机检测生成使用记录，使用记录可以手动关联检测记录
$config['eq_record.edit_view'][] = 'Sample_Form_Record::record_edit_view';
$config['extra.form.validate'][] = 'Sample_Form_Record::post_form_validate';
$config['eq_record.edit_submit'][] = 'Sample_Form_Record::post_form_submit';
$config['eq_record_model.call.is_locked'][] = ['callback' => 'Sample_Form_Record::object_is_locked', 'weight' => -5];

// 检测收费, 计费收取申请者的费用，不影响机主使用记录产生的收费情况
$config['eq_record_model.saved'][] = 'Sample_Form_Charge::on_eq_record_saved';
$config['eq_record_model.deleted'][] = 'Sample_Form_Charge::on_eq_record_deleted';
$config['eq_record_model.before_delete'][] = 'Sample_Form_Record::on_eq_record_before_delete';

$config['equipment_model.saved'][] = 'Sample_Form::on_equipment_saved';
//获取当前使用记录的关联检测
$config['sample_form.charge_get_source'][] = 'Sample_Form::charge_get_source';
$config['eq_record.description'][] = ['callback' => 'Sample_Form::record_description', 'weight' => -999];
$config['is_allowed_to[锁定].eq_record'][] = ['callback' => 'Sample_Form_Record::record_ACL', 'weight' => -999];
