<?php

$config['admin.equipment.secondary_tabs'][] = 'EQ_Sample_Admin::secondary_tabs';

/*
NO.TASK#282(guoping.zhang@2010.12.01)
仪器送样预约功能
*/
$config['controller[!equipments/extra/index].ready'][] = 'EQ_Sample::setup_index';
$config['controller[!equipments/equipment/edit].ready'][] = 'EQ_Sample::setup_edit';
$config['controller[!equipments/equipment/index].ready'][] = 'EQ_Sample::setup_view';
/*
  用户能够在自己的档案页面下查看自己的”送样”, 这个页面需要列出仪器名称
  http://dev.geneegroup.com/doku.php/lims-cf-design/eq_sample_record_attachments
  (xiaopei.li@2011.08.03)
 */
$config['controller[!people/profile].ready'][] = 'EQ_Sample::setup_profile';
$config['controller[!labs/lab/index].ready'][] = 'EQ_Sample::setup_lab';

$config['eq_sample_model.before_save'][] = 'EQ_Sample::sample_before_save';
$config['eq_sample_model.saved'][] = 'EQ_Sample::on_sample_saved';
/*
NO.TASK#282(guoping.zhang@2010.12.02)
仪器送样预约功能权限绑定
*/
$config['is_allowed_to[查看送样设置].equipment'][] = 'EQ_Sample_Access::equipment_ACL';
$config['is_allowed_to[修改送样设置].equipment'][] = 'EQ_Sample_Access::equipment_ACL';
$config['is_allowed_to[添加送样请求].equipment'][] = 'EQ_Sample_Access::equipment_ACL';
$config['is_allowed_to[列表送样请求].equipment'][] = 'EQ_Sample_Access::equipment_ACL';
$config['is_allowed_to[导出送样记录].equipment'][] = 'EQ_Sample_Access::equipment_ACL';

$config['is_allowed_to[查看].eq_sample'][] = 'EQ_Sample_Access::eq_sample_ACL';
$config['is_allowed_to[修改].eq_sample'][] = 'EQ_Sample_Access::eq_sample_ACL';
$config['is_allowed_to[删除].eq_sample'][] = 'EQ_Sample_Access::eq_sample_ACL';
$config['is_allowed_to[管理].eq_sample'][] = 'EQ_Sample_Access::eq_sample_ACL';
$config['is_allowed_to[反馈].eq_sample'][] = 'EQ_Sample_Access::eq_sample_ACL';
$config['is_allowed_to[发送消息].eq_sample'][] = 'EQ_Sample_Access::eq_sample_ACL';
$config['is_allowed_to[发送报告].eq_sample'][] = 'EQ_Sample_Access::eq_sample_ACL';
$config['is_allowed_to[锁定送样].equipment'][] = 'EQ_Sample_Access::equipment_ACL';
$config['is_allowed_to[查看所有送样记录].equipment'][] = 'EQ_Sample_Access::equipment_ACL';

/*
 * 增加仪器送样hook
 */
$config['is_allowed_to[添加送样记录].equipment'][] = 'EQ_Sample_Access::equipment_ACL';

/**
 * 为仪器名称添加状态信息
 */
$config['equipment.status_tag'][] = 'EQ_Sample::equipment_status_tag';


/*
  TASK #1266::Eq_sample的附件上传下载功能
  (xiaopei.li@2011.08.03)
 */
$config['is_allowed_to[列表文件].eq_sample'][] = 'EQ_Sample_Access::eq_sample_attachments_ACL';
$config['is_allowed_to[下载文件].eq_sample'][] = 'EQ_Sample_Access::eq_sample_attachments_ACL';
$config['is_allowed_to[上传文件].eq_sample'][] = 'EQ_Sample_Access::eq_sample_attachments_ACL';
$config['is_allowed_to[修改文件].eq_sample'][] = 'EQ_Sample_Access::eq_sample_attachments_ACL';
$config['is_allowed_to[删除文件].eq_sample'][] = 'EQ_Sample_Access::eq_sample_attachments_ACL';

$config['message.title.get'][] = 'EQ_Sample::get_message_title';

$config['user.before_delete_message'][] = 'EQ_Sample::before_user_save_message';

$config['is_allowed_to[列表个人页面送样预约].user'][] = 'EQ_Sample_Access::user_ACL';
$config['is_allowed_to[导出个人页面送样预约].user'][] = 'EQ_Sample_Access::user_ACL';

$config['extra.form.validate'][] = 'EQ_Sample::extra_form_validate';
$config['eq_record.edit_submit'][] = 'EQ_Sample::record_edit_submit';
$config['eq_record.edit_view'][] = 'EQ_Sample::record_edit_view';
$config['eq_record.notes_view'][] = 'EQ_Sample::record_notes_view';
$config['eq_record.description'][] = 'EQ_Sample::record_description';
$config['eq_record.notes_csv'][] = 'EQ_Sample::record_description_csv';

//perms start
$config['is_allowed_to[添加事件].calendar'][] = 'EQ_Sample_Access::calendar_ACL';
$config['is_allowed_to[修改事件].calendar'][] = 'EQ_Sample_Access::calendar_ACL';
$config['is_allowed_to[列表事件].calendar'][] = 'EQ_Sample_Access::calendar_ACL';
$config['is_allowed_to[添加重复规则].calendar'][] = 'EQ_Sample_Access::calendar_ACL';

$config['is_allowed_to[添加].cal_component'][] = 'EQ_Sample_Access::component_ACL';
$config['is_allowed_to[删除].cal_component'][] = 'EQ_Sample_Access::component_ACL';
$config['is_allowed_to[修改].cal_component'][] = 'EQ_Sample_Access::component_ACL';
$config['is_allowed_to[查看].cal_component'][] = 'EQ_Sample_Access::component_ACL';
//perms end

$config['calendar.lines.get'][] = 'EQ_Sample::calendar_lines_get';

$config['calendar.components.get'][] = 'EQ_Sample::calendar_components_get';
$config['calendar.component_content.render'][] = 'EQ_Sample::calendar_components_content';

$config['eq_sample_model.call.is_locked'][] = 'EQ_Sample::is_locked';

$config['controller[!equipments/equipment/extra_setting].ready'][] = 'EQ_Sample::setup_extra_setting';
$config['equipment.extra_setting.breadcrumb'][] = 'EQ_Sample::extra_setting_breadcrumb';

$config['extra.settings.adopted_view[equipment.eq_sample]'][] = 'EQ_Sample::default_extra_setting_view';

$config['extra.check_field_title'][] = 'EQ_Sample::extra_check_field_title';

$config['is_allowed_to[列表仪器送样].lab'][] = 'EQ_Sample_Access::lab_ACL';
$config['user_model.perms.enumerates'][] = 'EQ_Sample::on_enumerate_user_perms';

$config['eq_sample_model.call.eq_sample_view_print'][] = 'EQ_Sample::eq_sample_view_print';

$config['eq_sample_table.prerender'][] = 'EQ_Sample::eq_sample_table_column';

$config['eq_sample.extra.export_columns'][] = 'EQ_Sample::extra_export_columns';

$config['eq_sample.table_list.columns'][] = 'EQ_Sample::eq_sample_list_columns';
$config['eq_sample.table_list.row'][] = 'EQ_Sample::eq_sample_list_row';

$config['eq_sample.pending.count'][] = 'EQ_Sample::pending_count';

$config['sample.status'][] = ['callback' => 'EQ_Sample::status', 'weight' => 999];
$config['sample.colors'][] = ['callback' => 'EQ_Sample::colors', 'weight' => 999];
$config['sample.charge_status'][] = ['callback' => 'EQ_Sample::charge_status', 'weight' => 999];

//RQ184007—用户在填写送样表单时，增加已有人数
$config['eq_sample.prerender.add.form'][] = ['callback' => 'EQ_Sample::show_queue_numbers', 'weight' => -999];
$config['eq_sample.prerender.edit.form'][] = ['callback' => 'EQ_Sample::show_queue_numbers', 'weight' => -999];

// bug 16530 送样最早/最晚时间校验
$config['extra.form.validate'][] = 'EQ_Sample::check_limit_time';

$config['equipment.view.dashboard.sections'][] = 'EQ_Sample::equipment_dashboard_sections';

$config['calendar.calendar_left_content.get'][] = 'EQ_Sample::get_calendar_left_content';
// $config['sample.status.step'][] = 'EQ_Sample::sample_status_step';
$config['sample.status.step'][] = 'EQ_Sample::status_step';

/**
 * 加入送样PI审核相关功能
 */
$config['eq_sample_model.before_save'][] = 'EQ_Sample::eq_sample_model_before_save';


$config['eq_sample.extra.export_columns'][] = 'Eq_Sample::extra_export_columns';
$config['eq_sample.export_list_csv'][] = 'Eq_Sample::eq_sample_export_csv';
$config['eq_sample.extra.display_none'][] = 'Eq_Sample::extra_display_none';

$config['api.v1.equipment-samples.GET'][] = 'Eq_Sample_API::equipment_samples_get';
$config['api.v1.equipment-sample.POST'][] = 'Eq_Sample_API::equipment_sample_post';
$config['api.v1.equipment-sample.PATCH'][] = 'Eq_Sample_API::equipment_sample_patch';
$config['api.v1.equipment-sample.DELETE'][] = 'Eq_Sample_API::equipment_sample_delete';
$config['equipment.api.v1.samples.GET'][] = 'Eq_Sample_API::equipment_samples_get';
$config['equipment.api.v1.sample.GET'][] = 'Eq_Sample_API::equipment_sample_get';
$config['equipment.api.v1.sample.POST'][] = 'Eq_Sample_API::equipment_sample_post';
$config['equipment.api.v1.sample.PATCH'][] = 'Eq_Sample_API::equipment_sample_patch';
$config['equipment.api.v1.sample.DELETE'][] = 'Eq_Sample_API::equipment_sample_delete';
$config['equipment.api.v1.sample-permission.POST'][] = 'Eq_Sample_API::sample_permission_POST';


$config['sample.form.submit'][] = 'EQ_Sample::duty_teacher_extra_form_submit';
$config['eq_sample.table_list.columns'][] = 'EQ_Sample::duty_teacher_sample_table_list_columns';
$config['eq_sample.table_list.row'][] = 'EQ_Sample::duty_teacher_sample_table_list_row';
$config['eq_sample.extra.export_columns'][] = 'EQ_Sample::duty_teacher_extra_export_columns';
