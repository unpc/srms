<?php

// 初始化模块加载
// $config['create_orm_tables'][] = 'Equipments::create_orm_tables';
$config['create_orm_tables'][] = 'Equipments::data_init';

$config['system.ready'][] = 'Equipments::get_equipment_count';

$config['controller[admin/index].ready'][] = 'Equipments_Admin::setup';
$config['controller[admin/index].ready'][] = 'Equipments_Support::setup';
$config['controller[!update].ready'][] = 'Equipments::setup_update';
$config['controller[!people/profile].ready'][] = 'Equipments::setup_profile';
$config['controller[!equipments/equipment/index].ready'][] = 'Training::setup_view';
$config['controller[!equipments/equipment/index].ready'][] = ['callback' => 'Equipments::force_read', 'weight' => -999];
$config['controller[!labs/lab/index].ready'][] = 'Equipments::setup_lab';

$config['equipment_model.updating'][] = 'Equipments::get_update_parameter';
$config['equipment_model.update.message'][] = 'Equipments::get_update_message';
$config['equipment_model.update.message_view'][] = 'Equipments::get_update_message_view';
$config['equipment.feedback.status'][] = 'Equipments::feedback_status';
//$config['equipment_model.get'][] = array('callback'=>'Equipments::on_equipment_get', 'weight'=>-1);
$config['equipment_model.call.cannot_access'][] = ['callback' => 'Equipments::cannot_access_equipment', 'weight' => -1];
//Equipments::cannot_reserv_equipment 并没有这个静态方法，暂时进行注释处理
//$config['equipment_model.call.cannot_be_reserved'][] = array('callback' => 'Equipments::cannot_reserv_equipment', 'weight' => -1);

$config['equipment[add].post_submit_saved'][] = 'Equipments::post_submit_saved';

$config['eq_record_model.saved'][] = 'Equipments::on_record_saved';
$config['eq_record_model.before_save'][] = 'Equipments::before_record_save';
$config['eq_record_model.call.is_timespan_locked'][] = 'EQ_Record::is_timespan_locked';

$config['eq_record.judge.balance'][] = 'EQ_Record::judge_balance';

// $config['eq_record.get_nofeedback'][] = 'Equipments::nofeedback_record';
$config['ue_training_model.saved'][] = 'Equipments::on_training_saved';
$config['ue_training_model.before_delete'][] = 'Equipments::on_training_delete';
$config['achievement.project.select'][] = 'Equipments::project_equipments_get';

$config['publication_model.before_delete'][] = 'Equipments::before_equipment_relation_delete';
$config['patent_model.before_delete'][] = 'Equipments::before_equipment_relation_delete';
$config['award_model.before_delete'][] = 'Equipments::before_equipment_relation_delete';

$config['user_model.perms.enumerates'][] = 'Equipments::on_enumerate_user_perms';
$config['user.before_delete_message'][] = 'Eq_Record::before_user_save_message';
$config['user.before_delete_message'][] = 'Training::before_user_save_message';


$config['achievements.publication.validate'][] = 'Equipments::on_achievement_validate';
$config['achievements.patent.validate'][] = 'Equipments::on_achievement_validate';
$config['achievements.award.validate'][] = 'Equipments::on_achievement_validate';
$config['achievements.publication.edit'][] = ['callback' => 'Equipments::on_achievement_edit', 'weight' => 10];
$config['achievements.patent.edit'][] = ['callback' => 'Equipments::on_achievement_edit', 'weight' => 10];
$config['achievements.award.edit'][] = ['callback' => 'Equipments::on_achievement_edit', 'weight' => 10];
$config['achievements.publication.save_access'][] = 'Equipments::on_achievements_saved';
$config['achievements.patent.save_access'][] = 'Equipments::on_achievements_saved';
$config['achievements.award.save_access'][] = 'Equipments::on_achievements_saved';

/*
	绑定处理equipment指定操作的权限判断的方法
*/
//对仪器本身属性进行控制的hook
$config['is_allowed_to[查看].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[列表].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[添加].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[修改].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[删除].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[隐藏].equipment'][] = 'Equipments::equipment_ACL';


$config['is_allowed_to[修改基本信息].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[修改组织机构].equipment'][] = 'Equipments::equipment_ACL';

$config['is_allowed_to[修改使用设置].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[锁定基本].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[提交修改].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[修改标签].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[管理培训].equipment'][] = 'Equipments::equipment_ACL';

$config['is_allowed_to[查看相关人员联系方式].equipment'][] = 'Equipments::equipment_ACL';
//锁定机主控制方式
$config['is_allowed_to[锁定机主控制方式].equipment'][] = 'Equipments::equipment_ACL';

//对仪器附件进行控制的hook
$config['is_allowed_to[列表文件].equipment'][] = 'Equipments::equipment_attachments_ACL';
$config['is_allowed_to[上传文件].equipment'][] = 'Equipments::equipment_attachments_ACL';
$config['is_allowed_to[下载文件].equipment'][] = 'Equipments::equipment_attachments_ACL';
$config['is_allowed_to[修改文件].equipment'][] = 'Equipments::equipment_attachments_ACL';
$config['is_allowed_to[删除文件].equipment'][] = 'Equipments::equipment_attachments_ACL';
$config['is_allowed_to[创建目录].equipment'][] = 'Equipments::equipment_attachments_ACL';
$config['is_allowed_to[修改目录].equipment'][] = 'Equipments::equipment_attachments_ACL';
$config['is_allowed_to[删除目录].equipment'][] = 'Equipments::equipment_attachments_ACL';

$config['is_allowed_to[查看仪器状态记录].equipment'][] = 'Equipments::equipments_list_ACL';
$config['is_allowed_to[查看仪器公告].equipment'][] = 'Equipments::equipments_list_ACL';
$config['is_allowed_to[查看仪器使用记录].equipment'][] = 'Equipments::equipments_list_ACL';
$config['is_allowed_to[查看仪器收费记录].equipment'][] = 'Equipments::equipments_list_ACL';
$config['is_allowed_to[显示仪器计费设置].equipment'][] = 'Equipments::equipments_list_ACL';
$config['is_allowed_to[显示仪器关注].equipment'][] = 'Equipments::equipments_list_ACL';

$config['is_allowed_to[列表仪器考试记录].equipment'][] = 'Equipments::equipment_records_ACL';
$config['is_allowed_to[列表仪器使用记录].lab'][] = 'Equipments::lab_equipments_records_ACL';
$config['is_allowed_to[列表仪器使用记录].equipment'][] = 'Equipments::equipment_records_ACL';
/* xiaopei.li@2011.02.22 */
$config['is_allowed_to[列表所有仪器使用记录].equipment'][] = 'Equipments::all_equipments_records_ACL';
$config['is_allowed_to[列表组织机构仪器使用记录].equipment'][] = 'Equipments::group_equipments_records_ACL';
$config['is_allowed_to[列表负责仪器使用记录].equipment'][] = 'Equipments::incharge_equipments_records_ACL';
$config['is_allowed_to[添加仪器使用记录].equipment'][] = 'Equipments::equipment_records_ACL';
$config['is_allowed_to[修改仪器使用记录].equipment'][] = 'Equipments::equipment_records_ACL';
$config['is_allowed_to[管理使用].equipment'][] = 'Equipments::equipment_records_ACL';

//对于用户控制处理的hook
$config['is_allowed_to[列表关注].user'][] = 'Equipments::user_ACL';
$config['is_allowed_to[列表关注的仪器].user'][] = 'Equipments::user_ACL';
$config['is_allowed_to[列表个人仪器使用记录].user'][] = 'Equipments::user_ACL';

$config['is_allowed_to[关注].equipment'][] = 'Equipments::user_ACL';
$config['is_allowed_to[取消关注].equipment'][] = 'Equipments::user_ACL';

//对eq_record使用记录控制的hook
$config['is_allowed_to[修改].eq_record'][] = 'Equipments::record_ACL';
$config['is_allowed_to[删除].eq_record'][] = 'Equipments::record_ACL';
$config['is_allowed_to[反馈].eq_record'][] = 'Equipments::record_ACL';
$config['is_allowed_to[修改代开者].eq_record'][] = 'Equipments::record_ACL';
$config['is_allowed_to[锁定].eq_record'][] = 'Equipments::record_ACL';
$config['is_allowed_to[修改开始时间].eq_record'][] = 'Equipments::record_ACL';
$config['is_allowed_to[修改结束时间].eq_record'][] = 'Equipments::record_ACL';
$config['is_allowed_to[修改样品数].eq_record'][] = 'Equipments::record_ACL';
$config['is_allowed_to[锁定送样数].eq_record'][] = 'Equipments::record_ACL';
/*
NO.BUG#264(guoping.zhang@2010.12.22)
修改仪器状态记录时添加新权限
*/
$config['is_allowed_to[修改仪器状态设置].equipment'][] = 'Equipments::status_ACL';
$config['is_allowed_to[报废仪器].equipment'][] = 'Equipments::status_ACL';

/* (xiaopei.li@2011.04.11) */
$config['is_allowed_to[添加公告].equipment'][] = 'Equipments::announce_ACL';
$config['is_allowed_to[修改公告].equipment'][] = 'Equipments::announce_ACL';
$config['is_allowed_to[删除公告].equipment'][] = 'Equipments::announce_ACL';

$config['is_allowed_to[管理仪器临时用户].equipment'][] = 'Equipments::create_temp_user_ACL';
$config['is_allowed_to[共享].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[进驻仪器控].equipment'][] = 'Equipments::equipment_ACL';
$config['is_allowed_to[修改代开者].equipment'][] = 'Equipments::equipment_ACL';

/*
  TASK #1267::Eq_record的附件上传下载功能
  (xiaopei.li@2011.08.03)
 */
$config['is_allowed_to[列表文件].eq_record'][] = 'EQ_Record::eq_record_attachments_ACL';
$config['is_allowed_to[下载文件].eq_record'][] = 'EQ_Record::eq_record_attachments_ACL';
$config['is_allowed_to[上传文件].eq_record'][] = 'EQ_Record::eq_record_attachments_ACL';
$config['is_allowed_to[修改文件].eq_record'][] = 'EQ_Record::eq_record_attachments_ACL';
$config['is_allowed_to[删除文件].eq_record'][] = 'EQ_Record::eq_record_attachments_ACL';


$config['is_allowed_to[机主预约审批].equipment'][] = 'Equipments::approval_ACL';


$config['get.equipment.simple.info'][] = 'Equipments::get_equipment_simple_info';

//打印、导出records的hooks
$config['print.equipment.records'][] = 'EQ_Record::print_equipment_records';
$config['export.equipment.records'][] = 'EQ_Record::export_equipment_records';
$config['get_array_csv_title'][] = 'EQ_Record::get_array_csv_title';
$config['newsletter.get_contents[security]'][] = 'Equipments::equipment_newsletter_content';

//打印、导出仪器
//$config['is_allowed_to[导出].user'][] = 'People::user_ACL';
$config['is_allowed_to[导出].equipment'][] = 'Equipments::equipment_ACL';

$config['eq_record_model.call.get_date'][] = 'EQ_Record::get_date';
$config['eq_record_model.call.get_duration'][] = 'EQ_Record::get_duration';
$config['eq_record_model.call.get_total_time'][] = 'EQ_Record::get_total_time';
$config['eq_record_model.call.get_total_time_hour'][] = 'EQ_Record::get_total_time_hour';
$content['eq_record_model.get.samples'][] = ['callback'=> 'EQ_Record::get_sapmles', 'weight'=> 0];

$config['equipment.extra_setting.breadcrumb'][] = 'Equipments::extra_setting_breadcrumb';
$config['equipment.extra_setting.content'][] = 'Equipments::extra_setting_content';
$config['extra.settings.adopted_view[equipment.use]'][] = 'Equipments::default_extra_setting_view';
$config['extra.check_field_title'][] = 'Equipments::extra_check_field_title';
$config['extra.form.validate'][] = 'EQ_Record::extra_form_validate';

//授权规则设置
// $config['equipment.empower_setting.breadcrumb'][] = 'EQ_Empower::empower_setting_breadcrumb';
// $config['equipment.empower_setting.content'][] = 'EQ_Empower::empower_setting_content';


//gmeter(epc) 相关
$config['equipment_model.before_save'][] = 'Equipments::before_equipment_save';
$config['equipment_model.deleted'][] = 'Equipments::on_equipment_deleted';
$config['equipment_model.saved'][] = 'Equipments::delete_equipment_gmeter';

// 个人门户对接hook
$config['application.component.views'][] = 'Equipments_Com::views';
$config['application.component.view.serviceCondition'][] = 'Equipments_Com::view_serviceCondition';
$config['application.component.view.useRank'][] = 'Equipments_Com::view_useRank';
$config['application.component.view.useProRank'][] = 'Equipments_Com::view_useProRank';
$config['application.component.view.equipmentCount'][] = 'Equipments_Com::view_equipmentCount';
$config['application.component.view.totalService'][] = 'Equipments_Com::view_totalService';
$config['application.component.view.sharingRate'][] = 'Equipments_Com::view_sharingRate';
$config['application.component.view.feedback'][] = 'Equipments_Com::view_feedback';

$config['application.component.settings.sharingRate'][] = 'Equipments_Com::settings_sharingRate';
$config['application.component.settings.useRank'][] = 'Equipments_Com::settings_useRank';
$config['application.component.settings.useProRank'][] = 'Equipments_Com::settings_useProRank';

$config['equipments.export_columns.eq_record.new'][] = 'EQ_Record::export_record_columns';
$config['equipments.get.export.record.columns'][] = 'EQ_Record::get_export_record_columns';
$config['eq_record.list.columns'][] = 'EQ_Record::eq_record_list_columns';
$config['eq_record.list.row'][] = 'EQ_Record::eq_record_list_row';

$config['auth.login'][] = 'EQ_Record::auth_login';
$config['layout_controller_after_call'][] = 'EQ_Record::layout_after_call';

// glogon 额外信息
$config['equipments.glogon.ret'][] = 'Equipment_Glogon::glogon_ret';
$config['equipments.glogon.login'][] = 'EQ_Record::glogon_login';

$config['veronica.extra.login.view'][] = 'Equipment_Veronica::extra_login_view';
$config['veronica.extra.login.validate'][] = 'Equipment_Veronica::extra_login_validate';
$config['veronica.extra.login.offline.after'][] = 'Equipment_Veronica::extra_switch_on_before';
$config['veronica.extra.switch_on.before'][] = 'Equipment_Veronica::extra_switch_on_before';

//贵重仪器设备年使用情况表
$config['equipment.view.dashboard.sections'][] = 'Equipment_Stat::get_view_dashboard_sections';

$config['equipment.view.dashboard.sections'][] = 'Equipment_Preheat_Cooling::get_view_dashboard_sections';

$config['eq_stat.sj_tri.used_samples'][] = 'Equipment_Stat::sj_tri_used_samples';

$config['eq_stat.sj_tri.used_charge'][] = 'Equipment_Stat::sj_tri_used_charge';

$config['eq_stat.sj_tri.train_count'][] = 'Equipment_Stat::sj_tri_train_count';

$config['eq_stat.sj_tri.train_stu_count'][] = 'Equipment_Stat::sj_tri_train_stu_count';

$config['eq_stat.sj_tri.train_tea_count'][] = 'Equipment_Stat::sj_tri_train_tea_count';

$config['eq_stat.sj_tri.train_oth_count'][] = 'Equipment_Stat::sj_tri_train_oth_count';

$config['eq_stat.sj_tri.education_pro_count'][] = 'Equipment_Stat::sj_tri_education_pro_count';

$config['eq_stat.sj_tri.research_pro_count'][] = 'Equipment_Stat::sj_tri_research_pro_count';

$config['eq_stat.sj_tri.service_pro_count'][] = 'Equipment_Stat::sj_tri_service_pro_count';

$config['eq_record.list.columns'][] = ['callback' => 'Eq_Record::eq_record_list_columns_sorted', 'weight' => 999];

$config['eq_record.sort_str_factory'][] = ['callback' => 'Eq_Record::sort_str_factory', 'weight' => 999];

$config['eq_record_model.before_save'][] = ['callback' => 'Eq_Record::eq_record_before_save', 'weight' => 999];

$config['eq_training.pending.count'][] = 'Training::eq_training_pending_count';
$config['eq_job.pending.count'][] = 'Training::eq_job_pending_count';

$config['enable.announcemente'][] = 'Equipments::enable_announcemente';

$config['equipment_edit_use.form.validate'][] = 'Equipment_Preheat_Cooling::equipment_edit_use_form_validate';
$config['equipments_edit_use_submit'][] = 'Equipment_Preheat_Cooling::edit_use';
$config['extra.form.validate'][] = 'Equipment_Preheat_Cooling::extra_form_validate';
// Equipment_Preheat_Cooling::eq_record_form_submit 有关于使用记录关联送样记录结果的查询, 故weight999
$config['eq_record.edit_submit'][] = ['callback' => 'Equipment_Preheat_Cooling::eq_record_form_submit', 'weight' => 999];
$config['sample.form.submit'][] = 'Equipment_Preheat_Cooling::sample_form_submit';

$config['module[equipments_records].is_accessible'][] = 'Equipments::is_accessible';

$config['equipment_training.list.columns'][] = 'Training::equipment_training_list_columns';
$config['equipment_training.list.row.applied'][] = 'Training::equipment_training_list_row_applied';


$config['ue_training_model.before_delete'][] = 'Training::on_training_delete';
$config['equipments_edit_use_view'][] = 'Training::training_edit_use_view';
$config['equipments_edit_use_submit'][] = 'Training::training_edit_use_submit';

// 培训有效期限制消息提醒
$config['billing_notification.extra_display'][] = 'Training::notification_extra_display';
$config['layout_controller_after_call'][] = 'Equipments_Admin::layout_after_call';
// glogon使用超时提醒
$config['equipments.glogon.ret'][] = 'Equipments_Admin::equipments_glogon_ret';
//通用hook，主要做脚本可视化变量替换保存
$config['equipment.custom_content'][] = 'Equipments::custom_content';
$config['equipment.custom_content_empty'][] = 'Equipments::custom_content_empty';
$config['equipment.edit.get.disable.lock_incharge_control'][] = "Equipments::lock_incharge_control_set";

// 仪器selector的sort部分trigger出去, 满足仪器列表排序定制需求
$config['equipment.sort.selector'][] = 'Equipments::equipment_sort_selector';

$config['eq_record.extra.display_none'][] = 'EQ_Record::use_extra_display_none';
$config['equipments.get.export.record.columns'][] = 'EQ_Record::get_export_record_columns';
$config['eq_record.export_list_csv'][] = 'EQ_Record::eq_record_export_list_csv';
$config['equipments.export_columns.eq_record.new'][] = 'EQ_Record::export_record_columns';

$config['equipment_training.list.columns.applied'][] = 'Training::equipment_training_list_columns';
$config['equipment_training.list.row.applied'][] = 'Training::equipment_training_list_row_applied';

$config['view[calendar/permission_check].prerender'][] = 'Training::reserv_permission_check';
$config['view[calendar/permission_check].prerender'][] = 'Equipment_ACL::reserv_permission_check';
$config['view[calendar/permission_check].prerender'][] = 'Equipments::reserv_permission_check';
$config['api.v1.binding.GET'][] = 'Equipment_API::binding_get';
$config['api.v1.binding.POST'][] = 'Equipment_API::binding_post';
$config['api.v1.binding.PATCH'][] = 'Equipment_API::binding_patch';
$config['api.v1.binding.DELETE'][] = 'Equipment_API::binding_delete';

$config['api.v1.bindings.GET'][] = 'Equipment_API::bindings_get';
$config['api.v1.bindings.PATCH'][] = 'Equipment_API::bindings_patch';

$config['api.v1.log-permission.POST'][] = 'Equipment_API::log_permission_post';
$config['api.v1.logs.GET'][] = 'Equipment_API::logs_get';
$config['api.v1.log.POST'][] = 'Equipment_API::log_post';
$config['api.v1.log.PATCH'][] = 'Equipment_API::log_patch';
$config['api.v1.log.DELETE'][] = 'Equipment_API::log_delete';
$config['api.v1.current-log.GET'][] = 'Equipment_API::current_log_get';
$config['api.v1.equipments.GET'][] = 'Equipment_API::equipments_get';
$config['api.v1.equipment.GET'][] = 'Equipment_API::equipment_get';
$config['api.v1.equipment-filters.GET'][] = 'Equipment_API::equipment_filters_get';
$config['api.v1.equipment-announcements.GET'][] = 'Equipment_API::equipment_announces_get';
$config['api.v1.equipment-announcement-permission.POST'][] = 'Equipment_API::announcement_permission_post';
$config['api.v1.equipment-announcement.PATCH'][] = 'Equipment_API::equipment_announces_patch';
$config['api.v1.equipment-state.PATCH'][] = 'Equipment_API::equipment_state_patch';
$config['api.v1.following-equipments.GET'][] = 'Equipment_API::follow_equipments_get';

$config['api.v1.equipment-trainings.GET'][] = 'Equipment_Training_API::equipment_trainings_get';
$config['api.v1.equipment-training.POST'][] = 'Equipment_Training_API::equipment_training_post';
// $config['api.v1.equipment-training.PATCH'][] = 'Equipment_Training_API::equipment_training_patch';
$config['api.v1.tasks.GET'][] = 'Equipment_Approval_API::approval_tasks_get';
$config['api.v1.task.complete.POST'][] = 'Equipment_Approval_API::approval_task_complete_post';

$config['equipments_edit_use_view'][] = 'Equipments::duty_teacher_edit_use_view';
$config['equipments_edit_use_submit'][] = 'Equipments::duty_teacher_edit_use_submit';
$config['controller[!equipments/extra/index].ready'][] = 'Eq_record::setup_index';

$config['controller[!equipments/equipment/edit].ready'][] = ['callback' => 'Equipments::setup_equipment', 'weight' => 0];

$config['equipments_edit_use_extra_view'][] = 'Equipments::equipments_edit_use_extra_view';
$config['equipment_edit_use.form.validate'][] = 'Equipments::equipment_edit_extra_form_validate';
$config['equipments_edit_use_submit'][] = 'Equipments::equipment_use_extra_save';

$config['notification.get_template'][] = 'Equipments::get_template';
$config['notification.get_template_name'][] = 'Equipments::get_template_name';
$config['equipment.api.v1.list.GET'][] = 'Equipment_API::equipments_get';
$config['equipment.api.v1.GET'][] = 'Equipment_API::equipment_get';
$config['equipment.api.v1.filters.GET'][] = 'Equipment_API::equipment_filters_get';
$config['equipment.api.v1.following.GET'][] = 'Equipment_API::follow_equipments_get';
$config['equipment.api.v1.announcements.GET'][] = 'Equipment_API::equipment_announces_get';
$config['equipment.api.v1.announcement-permission.POST'][] = 'Equipment_API::announcement_permission_post';
$config['equipment.api.v1.announcement.PATCH'][] = 'Equipment_API::equipment_announces_patch';
$config['equipment.api.v1.binding.GET'][] = 'Equipment_API::binding_get';
$config['equipment.api.v1.binding.POST'][] = 'Equipment_API::binding_post';
$config['equipment.api.v1.binding.PATCH'][] = 'Equipment_API::binding_patch';
$config['equipment.api.v1.binding.DELETE'][] = 'Equipment_API::binding_delete';
$config['equipment.api.v1.bindings.GET'][] = 'Equipment_API::bindings_get';
$config['equipment.api.v1.current-log.GET'][] = 'Equipment_API::current_log_get';
$config['equipment.api.v1.log.feedback.PATCH'][] = 'Equipment_API::feedback_patch';
$config['equipment.api.v1.logs.GET'][] = 'Equipment_API::logs_get';
$config['equipment.api.v1.log.PATCH'][] = 'Equipment_API::log_patch';
$config['equipment.api.v1.log-user.POST'][] = 'Equipment_API::log_user_post';
$config['equipment.api.v1.log.DELETE'][] = 'Equipment_API::log_delete';
$config['equipment.api.v1.log-permission.POST'][] = 'Equipment_API::log_permission_post';
$config['equipment.api.v1.accept.PATCH'][] = 'Equipment_API::equipment_accept_patch';
$config['equipment.api.v1.permission.POST'][] = 'Equipment_API::equipment_permission_post';
$config['equipment.api.v1.stat.GET'][] = 'Equipment_API::equipment_stat_get';
$config['equipment.api.v1.state.PATCH'][] = 'Equipment_API::equipment_state_patch';
$config['equipment.api.v1.tasks.GET'][] = 'Equipment_Approval_API::approval_tasks_get';
$config['equipment.api.v1.task.complete.POST'][] = 'Equipment_Approval_API::approval_task_complete_post';
$config['equipment.api.v1.task.GET'][] = 'Equipment_Approval_API::approval_task_get';
$config['equipment.api.v1.training.GET'][] = 'Equipment_Training_API::equipment_training_get';
$config['equipment.api.v1.trainings.GET'][] = 'Equipment_Training_API::equipment_trainings_get';
$config['equipment.api.v1.training.POST'][] = 'Equipment_Training_API::equipment_training_post';
$config['equipment.api.v1.training.PATCH'][] = 'Equipment_Training_API::equipment_training_patch';

