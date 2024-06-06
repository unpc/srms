<?php
$config['eq_sample_model.call.is_locked'][] = ['callback' => 'EQ_Charge_Confirm::object_is_locked', 'weight' => -5];
$config['eq_reserv_model.call.is_locked'][] = ['callback' => 'EQ_Charge_Confirm::object_is_locked', 'weight' => -5];
$config['eq_record_model.call.is_locked'][] = ['callback' => 'EQ_Charge_Confirm::object_is_locked', 'weight' => -5];

$config['eq_charge.links'][] = 'EQ_Charge_Confirm::eq_charge_links';
$config['eq_charge_model.saved'][] = ['callback' => 'EQ_Charge_Confirm::on_eq_charge_saved', 'weight' => 100];

$config['module[eq_charge_confirm].is_accessible'][] = 'EQ_Charge_Confirm::is_accessible';
$config['is_allowed_to[收费确认].equipment'][] = 'EQ_Charge_Confirm::confirm_ACL';
$config['is_allowed_to[确认].eq_charge'][] = 'EQ_Charge_Confirm::charge_confirm_ACL';

// 确认收费后，使用记录不可以在被反馈
$config['is_allowed_to[反馈].eq_record'][] = ['callback' => 'EQ_Charge_Confirm::record_ACL', 'weight' => -5];
// 综合计费，预约和使用关联的情况下，预约记录被确认后，还可以编辑使用记录
$config['eq_record_model.call.is_locked'][] = 'EQ_Charge_Confirm::record_is_locked';