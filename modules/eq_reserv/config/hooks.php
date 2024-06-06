<?php

$config['controller[admin/index].ready'][] = 'EQ_Reserv_Admin::setup';

$config['controller[!equipments/extra/index].ready'][] = 'EQ_Reserv::setup_index';
$config['controller[!equipments/equipment/index].ready']['reserv'] = 'EQ_Reserv::setup_view';
$config['controller[!equipments/equipment/edit].ready'][] = 'EQ_Reserv::setup_edit';
$config['controller[!labs/lab/edit].ready'][] = 'EQ_Reserv::setup_edit';
$config['controller[!equipments/index].ready'][] = 'EQ_Reserv::setup_index';
$config['controller[!people/profile].ready']['reserv'] = 'EQ_Reserv::setup_index';
$config['controller[!people/profile].ready']['reserv'] = 'EQ_Reserv::setup_profile';
$config['controller[!labs/lab/index].ready'][] = 'EQ_Reserv::setup_lab';

$config['cal_component_model.before_save'][] = 'EQ_Reserv::cal_component_before_save';
$config['cal_component_model.saved'][] = 'EQ_Reserv::cal_component_saved';
$config['cal_component_model.saved'][] = 'Eq_Reserv_Notification::cal_component_saved';
$config['cal_component_model.deleted'][] = 'EQ_Reserv::cal_component_deleted';
$config['cal_component_model.deleted'][] = 'Eq_Reserv_Notification::cal_component_deleted';
$config['calendar.list_columns'][] = 'EQ_Reserv::calendar_list_columns';
$config['calendar.list_row'][] = 'EQ_Reserv::calendar_list_row';

$config['eq_record_model.deleted'][] = 'EQ_Reserv::on_record_deleted';
$config['eq_record_model.saved'][] = 'EQ_Reserv::on_record_saved';

//$config['equipment_model.get'][] = 'EQ_Reserv::on_equipment_get';
$config['equipment_model.call.cannot_access'][] = 'EQ_Reserv::cannot_access_equipment';
$config['equipment_model.call.cannot_be_reserved'][] = 'EQ_Reserv::cannot_reserv_equipment';

$config['equipment_model.saved'][] = 'EQ_Reserv::on_equipment_saved';
$config['eq_banned_model.deleted'][] = 'EQ_Reserv::on_banned_deleted';

$config['equipment_model.before_delete'][] = 'EQ_Reserv::before_equipment_delete';

$config['eq_reserv.on_change_reserv_status'][] = 'EQ_Reserv::on_change_reserv_status';

//绑定设备开关事件
$config['eq_record_model.before_save'][] = ['weight'=>'-1', 'callback'=>'EQ_Reserv::before_record_save'];
$config['eq_reserv_model.saved'][] = ['weight'=>'-1', 'callback'=>'EQ_Reserv::on_reserv_saved'];
$config['eq_reserv_model.deleted'][] = ['weight' => '-1', 'callback' => 'EQ_Reserv::on_reserv_deleted'];
$config['eq_reserv_model.saved'][] = ['weight' => 999, 'callback' => 'EQ_Reserv_Glogon::on_eq_reserv_changed'];
$config['eq_reserv_model.deleted'][] = ['weight' => 999, 'callback' => 'EQ_Reserv_Glogon::on_eq_reserv_changed'];

$config['eq_record.description'][] = 'EQ_Reserv::record_description';
$config['eq_record.notes_csv'][] = 'EQ_Reserv::record_description_csv';
//$config['eq_record.get_nofeedback'][] = array('callback'=>'EQ_Reserv::nofeedback_record', 'weight'=>-10);

//$config['equipment.record.links'][] = 'EQ_Reserv::record_links_extend';
$config['eq_status_model.saved'][] = 'Eq_Reserv_Notification::on_status_saved';
$config['eq_record.search_filter.view'][] = 'EQ_Reserv::search_filter_view';

//hook calendar的label
$config['view[calendar/component_form].prerender'][] = 'EQ_Reserv::prerender_component';
$config['view[calendar/component_info].prerender'][] = 'EQ_Reserv::prerender_component';
$config['calendar.component_content.render'][] = 'EQ_Reserv::component_content_render';
$config['calendar.component_list.render'][] = 'EQ_Reserv::component_list_render';

$config['user_model.perms.enumerates'][] = 'EQ_Reserv::on_enumerate_user_perms';
$config['user.before_delete_message'][] = 'EQ_Reserv::before_user_save_message';

$config['calendar.components.get'][] = 'EQ_Reserv::calendar_components_get';
$config['calendar.component.get_color'][] = 'EQ_reserv::cal_component_get_color';
$config['calendar.component_form.submit'][] = 'EQ_Reserv::component_form_submit';
$config['calendar.component_form.before_delete'][] = 'EQ_Reserv::component_form_before_delete';

$config['eq_record_model.updating'][] = 'EQ_Reserv::get_eq_record_update_parameter';
$config['model.updating'][] = 'EQ_Reserv::get_update_parameter';
$config['model.update.message'][] = 'EQ_Reserv::get_update_message';
$config['model.update.message_view'][] = 'EQ_Reserv::get_update_message_view';
/*
NO.TASK#265（guoping.zhang@2010.11.22)
EQ_Reserv模块新权限，绑定
*/
$config['is_allowed_to[添加].cal_component'][] = 'EQ_Reserv_Access::add_is_allowed';
$config['is_allowed_to[删除].cal_component'][] = 'EQ_Reserv_Access::delete_is_allowed';
$config['is_allowed_to[修改].cal_component'][] = 'EQ_Reserv_Access::edit_is_allowed';
$config['is_allowed_to[查看].cal_component'][] = 'EQ_Reserv_Access::view_is_allowed';

$config['is_allowed_to[添加事件].calendar'][] = 'EQ_Reserv_Access::add_event_is_allowed';
$config['is_allowed_to[修改事件].calendar'][] = 'EQ_Reserv_Access::edit_event_is_allowed';
$config['is_allowed_to[列表事件].calendar'][] = 'EQ_Reserv_Access::list_event_is_allowed';
$config['is_allowed_to[添加重复规则].calendar'][] = 'EQ_Reserv_Access::add_rrule_is_allowed';
//NO.TASK#199(guoping.zhang@2010.11.29)
$config['is_allowed_to[查看预约设置].equipment'][] = 'EQ_Reserv_Access::equipment_ACL';
$config['is_allowed_to[修改预约设置].equipment'][] = 'EQ_Reserv_Access::equipment_ACL';
$config['is_allowed_to[锁定预约].equipment'][] = 'EQ_Reserv_Access::equipment_ACL';
$config['is_allowed_to[修改预约].equipment'][] = 'EQ_Reserv_Access::equipment_ACL';

$config['is_allowed_to[修改预约违规次数].user'][] = 'EQ_Reserv_Access::user_ACL';
$config['is_allowed_to[列表仪器预约].lab'][] = 'EQ_Reserv_Access::lab_equipments_reserv_ACL';

/**
 * 为仪器名称添加状态信息
 */
$config['equipment.status_tag'][] = 'EQ_Reserv::equipment_status_tag';

$config['equipments.update.configs'][] = 'EQ_Reserv::get_equipments_updates_configs';
$config['people.update.configs'][] = 'EQ_Reserv::get_people_updates_configs';

$config['cal_component_model.call.check_overlap'][] = 'EQ_Reserv::is_check_overlap';

/*
	仪器预约的消息通知
*/
$config['admin.equipments.notification_configs'][] = 'EQ_Reserv::add_equipment_notification_config';
$config['other_notification.add.logs'][] = 'EQ_Reserv::equipments_add_logs';

// glogon 额外信息
$config['equipments.glogon.ret'][] = 'EQ_Reserv_Glogon::glogon_ret';
// 电脑控制仪器GLogon的预约事件更新
$config['device_computer.keep_alive'][] = 'EQ_Reserv::device_computer_keep_alive';
$config['calendar.list_empty_message'][] = 'EQ_Reserv::empty_eq_reserv_message'; 
$config['controller[!equipments/equipment/extra_setting].ready'][] = 'EQ_Reserv::setup_extra_setting';
$config['equipment.extra_setting.breadcrumb'][] = 'EQ_Reserv::extra_setting_breadcrumb';

$config['extra.settings.adopted_view[equipment.eq_reserv]'][] = 'EQ_Reserv::default_extra_setting_view';

// 自动合并预约相关
$config['eq_reserv_model.call.is_locked'][] = 'EQ_Reserv::is_reserv_locked';
$config['eq_reserv_model.call.get_status'][] = 'EQ_Reserv::get_status';
//获取calendar, 周、月、列表左边的view
$config['calendar.calendar_left_content.get'][] = 'EQ_Reserv::get_calendar_left_content';
$config['calendar.calendar_right_content.get'][] = 'EQ_Reserv::get_calendar_right_content';
//calendar在插入component时title显示hooks
$config['calendar.insert_component.title'][] = 'EQ_Reserv::insert_component_title';
$config['calendar.edit_component.title'][] = 'EQ_Reserv::edit_component_title';
$config['calendar.select_view_component.title'][] = 'EQ_Reserv::select_view_component_title';
$config['calendar.component_form.post_submit'][] = 'EQ_Reserv::component_form_post_submit';
$config['calendar.rrule_sub_component.saved'][] = 'EQ_Reserv::rrule_sub_component_saved';
$config['calendar.component_form.attempt_submit.log'][] = 'EQ_Reserv::get_equipment_calendar_log';

$config['extra.check_field_title'][] = 'EQ_Reserv::extra_check_field_title';

$config['eq_record_model.call.get_date'][] = 'EQ_Reserv::record_get_date';
$config['eq_record_model.call.get_duration'][] = 'EQ_Reserv::record_get_duration';
$config['eq_record_model.call.get_total_time'][] = 'EQ_Reserv::record_get_total_time';
$config['eq_record_model.call.get_total_time_hour'][] = 'EQ_Reserv::record_get_total_time';
$config['eq_record_model.get.samples'][] = ['callback'=> 'EQ_Reserv::record_get_samples', 'weight'=> -5];
$config['eq_record_model.call.cannot_lock_samples'][] = 'EQ_Reserv::record_cannot_lock_samples';

// 个人门户对接hook
$config['application.component.views'][] = 'EQ_Reserv_Com::views';
$config['application.component.view.plumpness'][] = 'EQ_Reserv_Com::view_plumpness';

$config['application.component.settings.plumpness'][] = 'EQ_Reserv_Com::settings_plumpness';
$config['user_lab.disconnect'][] = 'EQ_Reserv::on_user_disconnect_lab';

$config['eq_reserv.pending.count'][] = 'EQ_Reserv::pending_count';

// veronica和glogon分开
$config['eq_reserv.push_current_glogon'][] = 'eq_reserv_glogon::push_reserv_glogon';
$config['eq_reserv.push_current_veronica'][] = 'eq_reserv_glogon::push_reserv_veronica';
//RQ184811-北京大学-预约脚本可视化
$config['eq_reserv_script_visualization'][] = 'EQ_Reserv::script_visualization';
$config['equipment.custom_content'][] = 'Equipments::custom_content';


$config['calendar.extra.export_columns'][] = 'EQ_Reserv::calendar_extra_export_columns';
$config['calendar.export_list_csv'][] = 'EQ_Reserv::calendar_export_list_csv';
$config['eq_reserv.export_list_csv'][] = 'EQ_Reserv::eq_reserv_export_csv';

$config['api.v1.equipment-bookings.GET'][] = 'Eq_Reserv_API::equipment_bookings_get';
$config['api.v1.equipment-booking.DELETE'][] = 'Eq_Reserv_API::equipment_booking_delete';

$config['equipment.api.v1.bookings.GET'][] = 'Eq_Reserv_API::equipment_bookings_get';
$config['equipment.api.v1.booking.GET'][] = 'Eq_Reserv_API::equipment_booking_get';
$config['equipment.api.v1.booking.DELETE'][] = 'Eq_Reserv_API::equipment_booking_delete';

$config['add_component_validate'][] = 'EQ_Reserv::validate_block_time';
$config['update_component_validate'][] = 'EQ_Reserv::validate_block_time';

$config['eq_record.edit_view'][] = 'EQ_Reserv::record_edit_view';
$config['equipment.api.v1.reserv-permission.POST'][] = 'Eq_Reserv_API::reserv_permission_POST';
