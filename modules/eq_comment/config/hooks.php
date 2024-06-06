<?php
$config['controller[!equipments/extra/index].ready'][] = 'EQ_Comment_User::setup_extra';
$config['controller[!equipments/equipment/index].ready'][] = 'EQ_Comment_User::setup_equipment';
$config['controller[!people/profile].ready'][] = 'EQ_Comment_User::setup_profile';

$config['is_allowed_to[评价].eq_record'][] = 'EQ_Comment_User::comment_user_ACL';
$config['is_allowed_to[评价].eq_sample'][] = 'EQ_Comment_User::comment_user_ACL';
$config['is_allowed_to[评价机主].eq_record'][] = 'EQ_Comment_Incharge::comment_incharge_ACL';
$config['is_allowed_to[评价机主].eq_sample'][] = 'EQ_Comment_Incharge::comment_incharge_ACL';
$config['is_allowed_to[列表负责仪器使用评价].user'][] = 'EQ_Comment_User::comment_user_ACL';
$config['is_allowed_to[列表负责仪器使用评价].equipment'][] = 'EQ_Comment_User::comment_equipment_ACL';
$config['is_allowed_to[列表全部仪器使用评价].equipment'][] = 'EQ_Comment_User::comment_equipment_ACL';
$config['is_allowed_to[列表下属机构仪器使用评价].equipment'][] = 'EQ_Comment_User::comment_equipment_ACL';

$config['eq_record_model.before_save'][] = 'EQ_Comment_Incharge::eq_record_before_save';
$config['eq_sample_model.before_save'][] = 'EQ_Comment_Incharge::eq_sample_before_save';

// $config['sample.form.submit'][] = 'EQ_Comment_Incharge::sample_form_submit';
$config['extra.form.validate'][] = 'EQ_Comment_Incharge::extra_form_validate';

$config['record.links_edit'][] = 'EQ_Comment_User::eq_object_links_edit';
$config['eq_sample.links'][] = 'EQ_Comment_User::eq_object_links_edit';
$config['eq_sample.links'][] = 'EQ_Comment_Incharge::eq_sample_links_edit';
$config['eq_record_model.saved'][] = 'EQ_Comment_User::eq_object_model_saved';
$config['eq_sample_model.saved'][] = 'EQ_Comment_User::eq_object_model_saved';
$config['eq_record_model.saved'][] = 'EQ_Comment_Incharge::object_saved';
$config['eq_sample_model.saved'][] = 'EQ_Comment_Incharge::object_saved';
$config['feedback.form.submit'][] = 'EQ_Comment_Incharge::eq_comment_incharge_save';

$config['equipments.glogon.switch_to.logout.record_saved'][] = 'EQ_Comment_Incharge::glogon_switch_to_logout_record_saved';

// $config['feedback.need_evaluate_by_source'][] = ['callback'=>'EQ_Comment_Incharge::need_evaluate_by_source','weight'=>999];
