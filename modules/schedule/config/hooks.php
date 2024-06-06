<?php
$config['controller[admin/index].ready'][] = 'Schedule_Admin::setup';

$config['view[calendar/component_form].prerender'][] = 'Schedule::prerender_component';
$config['view[calendar/component_info].prerender'][] = 'Schedule::prerender_component';

$config['calendar.component_form.submit'][] = 'Schedule::component_form_submit';

$config['calendar.component_content.render'][] = 'Schedule::component_content_render';

$config['calendar.component_icon.present'][] = 'Schedule::component_icon_present';
$config['view[calendar/component_form].postrender'][] = ['callback'=>'Schedule::postrender_component', 'weight'=>-100];

$config['cal_component_model.saved'][] = 'Schedule::on_cal_component_saved';

$config['cal_component_model.deleted'][] = 'Schedule::on_cal_component_deleted';
/*
$config['cal_component_model.get'][] = 'Schedule::on_cal_component_get';
 */
$config['calendar.component.get_color'][] = 'Schedule::cal_component_get_color';
$config['calendar.components.get'][] = 'Schedule::calendar_components_get';

$config['calendar.component_form.before_delete'][] = 'Schedule::component_form_before_delete';

$config['controller[!schedule/index].ready'][] = 'Schedule::setup';
$config['controller[!people/profile/index].ready'][] = 'Schedule::profile_setup';

$config['user_model.perms.enumerates'][] = 'Schedule::on_enumerate_user_perms';
/*
NO.TASK#236（guoping.zhang@2010.11.16)
绑定处理指定对象指定操作的权限判断的方法
*/
$config['is_allowed_to[查看].cal_component'][] = 'Schedule_Access::view_is_allowed';
$config['is_allowed_to[添加].cal_component'][] = 'Schedule_Access::add_is_allowed';
$config['is_allowed_to[修改].cal_component'][] = 'Schedule_Access::edit_is_allowed';
$config['is_allowed_to[删除].cal_component'][] = 'Schedule_Access::delete_is_allowed';

$config['is_allowed_to[添加事件].calendar'][] = 'Schedule_Access::add_event_is_allowed';
$config['is_allowed_to[修改事件].calendar'][] = 'Schedule_Access::edit_event_is_allowed';
$config['is_allowed_to[列表事件].calendar'][] = 'Schedule_Access::list_event_is_allowed';
/*
NO.BUG#178（guoping.zhang@2010.11.20)
绑定处理日程附件操作权限
*/
$config['is_allowed_to[查看文件].cal_component'][] = 'Schedule_Access::attachment_is_allowed';
$config['is_allowed_to[修改文件].cal_component'][] = 'Schedule_Access::attachment_is_allowed';
$config['is_allowed_to[添加文件].cal_component'][] = 'Schedule_Access::attachment_is_allowed';
$config['is_allowed_to[删除文件].cal_component'][] = 'Schedule_Access::attachment_is_allowed';
$config['is_allowed_to[列表文件].cal_component'][] = 'Schedule_Access::attachment_is_allowed';
$config['is_allowed_to[上传文件].cal_component'][] = 'Schedule_Access::attachment_is_allowed';
$config['is_allowed_to[下载文件].cal_component'][] = 'Schedule_Access::attachment_is_allowed';


$config['get_schedule_component_ids'][] = 'Schedule::get_schedule_component_ids';
$config['newsletter.get_contents[schedule]'][] = 'Schedule::schedule_newsletter_content';

$config['meeting.export_columns.print'][] = 'Schedule::meeting_export_columns_print';
$config['meeting.export_columns.csv'][] = 'Schedule::meeting_export_columns_csv';

$config['calendar.list_empty_message'][] = 'Schedule::empty_schedule_message';

$config['calendar.insert_component.title'][] = 'Schedule::calendar_insert_component_title';
