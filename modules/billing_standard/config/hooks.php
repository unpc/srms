<?php
$config['module[billing_standard].is_accessible'][] = 'Billing_Standard::is_accessible';

//预约界面增加经费卡号视图
$config['eq_reserv.prerender.component']['billing_standard'] = 'Billing_Standard::eq_reserv_prerender_component';
//预约表单提交时候增加预约的grant属性
$config['eq_reserv.component.form.post.submit']['billing_standard'] = 'Billing_Standard::component_form_post_submit';
//仪器送样添加/编辑页面增加视图
$config['eq_sample.prerender.add.form']['billing_standard'] = 'Billing_Standard::eq_sample_prerender_add_form';
$config['eq_sample.prerender.edit.form']['billing_standard'] = 'Billing_Standard::eq_sample_prerender_edit_form';
//送样表单提交时候增加送样的grant属性
$config['sample.form.submit']['billing_standard'] = 'Billing_Standard::eq_sample_form_submit';
//表单提交时候的必填校验
$config['extra.form.validate']['billing_standard'] = 'Billing_Standard::extra_form_validate';
$config['billing_standard.not_must_select_fund']['billing_standard'] = 'Billing_Standard::not_must_select_fund';

//使用记录反馈时增加视图
$config['extra.feedback.fields.view']['billing_standard'] = 'Billing_Standard::feedback_extra_view';
//使用记录反馈时
$config['feedback.form.submit']['billing_standard'] = 'Billing_Standard::feedback_form_submit';
//$config['equipments.glogon.switch_to.logout.record_before_save']['billing_standard'] = 'billing_standard::glogon_logout_record_before_save';

// pi可为组内成员增加PI助理角色
$config['user_model.perms.enumerates']['billing_standard'] = 'Billing_Standard::on_enumerate_user_perms';
// 机主确认收费
$config['eq_charge.confirmed'][] = 'Billing_Standard::eq_charge_confirmed';

$config['eq_charge_model.saved'][] = 'Billing_Standard::eq_charge_saved';

$config['eq_charge_model.deleted'][] = 'Billing_Standard::on_eq_charge_deleted';

// 机主确认收费页面可以编辑
$config['eq_charge.links']['billing_standard'] = 'Billing_Standard::eq_charge_links';

//各类收费页面增加字段
$config['index_charges.table_list.columns']['billing_standard'] = 'Billing_Standard::charge_table_list_columns';
$config['index_charges.table_list.row']['billing_standard'] = 'Billing_Standard::charge_table_list_row';
$config['lab_charges.table_list.columns']['billing_standard'] = 'Billing_Standard::lab_charges_table_list_row';
$config['lab_charges.table_list.row']['billing_standard'] = 'Billing_Standard::lab_charges_list_columns';
$config['eq_charge.primary.content.selector'][] = 'Billing_Standard::eq_charge_primary_content_selector';
$config['eq_charge_all.primary.content.selector'][] = 'Billing_Standard::eq_charge_primary_content_selector';
$config['charges.table_list.search_box.extra_view'][] = 'Billing_Standard::charges_search_box_extra_view';

//增加打印列表字段
$config['eq_charge.export_columns'] = 'Billing_Standard::charge_export';

$config['is_allowed_to[取消确认].eq_charge'][] = 'Billing_Standard::charge_confirm_ACL';

$config['equipment_model.call.billing_department'][] = 'Billing_Standard::equipment_billing_department';


$config['eq_charge_model.call.get_fund_card_no'][] = 'Billing_Standard::charge_get_fund_card_no';


$config['equipment.reserv.extra.fields'][] = 'Yiqikong_Reserv_Billing_Standard::equipment_reserv_extra_fields';
$config['equipment.reserv.extra.fields.value'][] = 'Yiqikong_Reserv_Billing_Standard::equipment_reserv_extra_fields_value';
$config['add_component_validate'][] = 'Yiqikong_Reserv_Billing_Standard::yiqikong_component_validate';
$config['update_component_validate'][] = 'Yiqikong_Reserv_Billing_Standard::yiqikong_component_validate';
$config['add_component_submit'][] = 'Yiqikong_Reserv_Billing_Standard::yiqikong_component_submit';
$config['update_component_submit'][] = 'Yiqikong_Reserv_Billing_Standard::yiqikong_component_submit';

