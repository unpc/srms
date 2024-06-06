<?php
$config['controller[!equipments/equipment/index].ready'][] = ['callback' => 'EQ_Evaluate::setup_equipment', 'weight' => 0];
$config['controller[!equipments/extra/index].ready'][] = ['callback' => 'EQ_Evaluate::setup_extra', 'weight' => 0];
$config['controller[!equipments].ready'][] = ['callback' => 'EQ_Evaluate::setup', 'weight' => 0];

$config['feedback.form.submit'][] = 'EQ_Evaluate::eq_evaluate_save';
$config['eq_record_model.before_save'][] = 'EQ_Evaluate::eq_record_before_save';
$config['eq_record_model.before_delete'][] = 'EQ_Evaluate::eq_record_before_delete';

$config['equipments_edit_use_submit'][] = 'EQ_Evaluate::eq_allow_evaluate_save';
$config['equipments_edit_use_extra_view'][] = 'EQ_Evaluate::equipments_edit_use_extra_view';
$config['equipments.glogon.offline.logout.record_saved'][] = 'EQ_Evaluate::glogon_offline_logout_record_saved';
$config['equipments.glogon.switch_to.logout.record_saved'][] = 'EQ_Evaluate::glogon_switch_to_logout_record_saved';

$config['veronica.extra.logout.offline'][] = 'EQ_Evaluate::veronica_logout_extra';
$config['veronica.extra.switch_off.after'][] = 'EQ_Evaluate::veronica_logout_extra';

$config['is_allowed_to[查看评价].equipment'][] = 'EQ_Evaluate::equipment_evaluate_ACL';
$config['is_allowed_to[设置评价].equipment'][] = 'EQ_Evaluate::equipment_evaluate_ACL';

$config['try_create_record.before_save'][] = 'EQ_Evaluate::try_create_record_before_save';

$config['trigger_extra_primary_tabs'][] = 'EQ_Evaluate::evaluate_tab';
