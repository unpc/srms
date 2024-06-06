<?php
$config['admin.equipment.secondary_tabs'][] = 'EQ_Charge_Admin::secondary_tabs';
$config['controller[admin/index].ready'][] = 'EQ_Charge_Admin::setup';

$config['controller[!equipments/extra/index].ready'][] = 'EQ_Charge::setup_index';
$config['controller[!equipments/equipment/edit].ready'][] = 'EQ_Charge::setup_edit';
$config['controller[!equipments/equipment/index].ready'][] = 'EQ_Charge::setup_view';
$config['controller[!labs/lab/edit].ready'][] = 'EQ_Charge::setup_edit';

//Jia Huang @ 2011.2.26
//暂时隐藏个人页面的仪器使用收费, 个人页面应该有使用记录就足够了, 对个人使用收费的查询可以在实验室和仪器处进行
//Yu Li task 005815  个人信息页面增加“使用收费”
$config['controller[!people/profile/index].ready'][] = 'EQ_Charge::setup_profile';
$config['controller[!labs/lab/index].ready'][] = 'EQ_Charge::setup_lab';

$config['eq_record_model.before_save'][] = 'EQ_Charge::on_record_before_save';
$config['eq_record_model.saved'][] = 'EQ_Charge::on_record_saved';
$config['eq_record_model.deleted'][] = 'EQ_Charge::on_record_deleted';

$config['eq_reserv.on_set_agent'][] = 'EQ_Charge::on_set_agent';
$config['eq_reserv.get_charge'][] = 'EQ_Charge::get_charge';

$config['eq_record.description'][] = 'EQ_Charge::record_description';
$config['eq_record.notes_csv'][] = 'EQ_Charge::record_notes_csv';

$config['eq_record.edit_view'][] = 'EQ_Charge::record_edit_view';
//$config['eq_record.edit_submit'][] = 'EQ_Charge::record_edit_submit';

$config['profile.added_view'][] = 'EQ_Charge::profile_charge_view';

$config['user_model.perms.enumerates'][] = 'EQ_Charge::on_enumerate_user_perms';

$config['model.updating'][] = 'EQ_Charge::get_update_parameter';
$config['model.update.message'][] = 'EQ_Charge::get_update_message';
$config['model.update.message_view'][] = 'EQ_Charge::get_update_message_view';

//修改预约的预约块中的计费标签，自定义计费
$config['is_allowed_to[修改预约计费].cal_component'][] = 'EQ_Charge::cal_component_ACL';
$config['is_allowed_to[修改使用计费].eq_record'][] = 'EQ_Charge::record_ACL';
$config['is_allowed_to[修改送样计费].eq_sample'][] = 'EQ_Charge::sample_ACL';
//修改仪器收费设置的权限规则
$config['is_allowed_to[查看计费设置].equipment'][] = 'EQ_Charge::equipment_ACL';
$config['is_allowed_to[修改计费设置].equipment'][] = 'EQ_Charge::equipment_ACL';
$config['is_allowed_to[查看收费情况].equipment'][] = 'EQ_Charge::equipment_ACL';
$config['is_allowed_to[查看估计收费].equipment'][] = 'EQ_Charge::equipment_ACL';
$config['is_allowed_to[锁定计费].equipment'][] = 'EQ_Charge::equipment_ACL';
//查看实验室收费情况的权限规则
$config['is_allowed_to[查看收费情况].lab'][] = 'EQ_Charge::lab_ACL';
$config['is_allowed_to[查看估计收费].lab'][] = 'EQ_Charge::lab_ACL';

//查看个人收费情况的权限规则
$config['is_allowed_to[查看收费情况].user'][] = 'EQ_Charge::user_ACL';

$config['equipments.update.configs'][] = 'EQ_Charge::get_equipments_updates_configs';

$config['eq_charge_model.call.calculate_amount'][] = 'EQ_Charge::calculate_amount';
$config['eq_charge_model.call.is_locked'][] = 'EQ_Charge::charge_is_locked';
$config['eq_record_model.call.is_locked'][] = 'EQ_Charge::record_is_locked';
$config['eq_sample_model.call.is_locked'][] = 'EQ_Charge::sample_is_locked';
$config['eq_reserv_model.call.is_locked'][] = 'EQ_Charge::reserv_is_locked';

#翻译备注
$config['billing_transaction_model.call.description'][] = 'EQ_Charge::transaction_description';

//预约时相关计费选项
$config['view[calendar/component_form].prerender'][] = 'EQ_Charge::prerender_component';
$config['view[calendar/component_info].prerender'][] = 'EQ_Charge::prerender_component';

//calendar的表单提交事件
$config['calendar.component_form.submit'][] = 'EQ_Charge::component_form_submit';

$config['extra.form.post_submit'][] = 'EQ_Charge::record_form_submit';
$config['sample.form.submit'][] = 'EQ_Charge::sample_form_submit';

//针对于使用预约,送样预约都增加创建charge的处理
$config['eq_reserv_model.saved'][] = 'EQ_Charge::on_eq_reserv_saved';
$config['eq_reserv_model.deleted'][] = 'EQ_Charge::on_eq_reserv_deleted';
$config['eq_sample_model.saved'][] = 'EQ_Charge::on_eq_sample_saved';
$config['eq_sample_model.deleted'][] = 'EQ_Charge::on_eq_sample_deleted';

$config['eq_charge_model.before_save'][] = 'EQ_Charge::on_eq_charge_before_save';
$config['eq_charge_model.saved'][] = 'EQ_Charge::on_eq_charge_saved'; // 使用费用发生变化(发送消息提醒)

//自定义的模板，hook对应键值的事件
//预约模板
$config['template[time_reserv_record].setting_view'][] = 'EQ_Charge_Script::template_reserv_setting_view';
$config['template[only_reserv_time].setting_view'][] = 'EQ_Charge_Script::template_reserv_setting_view';
//使用模板
$config['template[record_time].setting_view'][] = 'EQ_Charge_Script::template_record_script_setting_view';
$config['template[record_times].setting_view'][] = 'EQ_Charge_Script::template_record_script_setting_view';

$config['template[record_samples].setting_view'][] = 'EQ_Charge_Script::template_record_script_setting_view';
//送样模板
$config['template[sample_count].setting_view'][] = 'EQ_Charge_Script::template_sample_count_setting_view';
$config['template[sample_time].setting_view'][] = 'EQ_Charge_Script::template_sample_time_script_setting_view';
$config['eq_sample.mail.content'][] = 'EQ_Charge::eq_sample_mail_content';
$config['eq_sample.view.print'][] = 'EQ_Charge::eq_sample_view_print';
//如果“需要预约”改变了
$config['equipment.accept_reserv.change'][] = 'EQ_Charge::equipment_accept_reserv_change';
//如果'接收送样'改变了
$config['equipment.accept_sample.change'][] = 'EQ_Charge::equipment_accept_sample_change';

//添加，修改送样dialog中的内容
$config['eq_sample.get_contents[add_sample_dialog]'][] = 'EQ_Charge::view_sample_dialog';
$config['eq_sample.get_contents[edit_sample_dialog]'][] = 'EQ_Charge::view_sample_dialog';

//送样列表页面table内容
$config['eq_sample.table_list.columns'][] = 'EQ_Charge::sample_table_list_columns';
$config['eq_sample.table_list.row'][] = 'EQ_Charge::sample_table_list_row';

// 个人门户对接hook
$config['application.component.views'][] = 'EQ_Charge_Com::views';
$config['application.component.view.tollSum'][] = 'EQ_Charge_Com::view_tollSum';

// RQ164411 送样收费预估
$config['eq_sample.charge_forecast'][] = "EQ_Charge::charge_forecast";

//RQ181907-机主编辑使用记录时有一个可以填写的备注框(通用可配)
$config['record_extra_charge'][] = "EQ_Charge::charge_desc_view";
$config['accept_sample.extra_value'] = "EQ_Charge::accept_sample_extra_value";

//计费设置中增加“折扣计费”收费方式
$config['template[record_time_discount].setting_view'][] = 'EQ_Charge_Script::template_record_script_setting_view';
//计算自定义计费
$config['eq_charge.equipment.record_time_discount'][] = 'Zone_EQ_Charge::record_time_discount';
$config['equipment_model.call.timezone'][] = 'EQ_Charge_Timezone::timezone';
$config['extra.form.validate'][] = 'EQ_Charge_Timezone::extra_form_validate';

$config['api.v1.equipment-charges.GET'][] = 'Eq_Charge_API::equipment_charges_get';
$config['equipment.api.v1.charges.GET'][] = 'Eq_Charge_API::equipment_charges_get';

$config['index_charges.table_list.columns'][] = 'Eq_Charge::charge_table_list_columns';
$config['index_charges.table_list.row'][] = 'Eq_Charge::charge_table_list_row';
$config['eq_charge.export_columns'] = 'Eq_Charge::charge_export';
$config['eq_charge_export.cloumns'][] = 'Eq_Charge::export_colums';