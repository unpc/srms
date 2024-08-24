<?php
$config['controller[admin/index].ready'][] = 'Meeting_Admin::setup';
$config['controller[admin/index].ready'][] = 'Meeting_Room::setup';
$config['controller[!meeting/meeting/index].ready'][] = 'Auths::setup_view';
$config['controller[!meeting/meeting/index].ready'][] = 'ME_Reserv::setup_view';
$config['controller[!entrance/door/index].ready'][] = 'Meeting_Door::setup';

$config['is_allowed_to[列表].meeting'][] = 'Meetings::meeting_ACL';
$config['is_allowed_to[添加].meeting'][] = 'Meetings::meeting_ACL';
$config['is_allowed_to[修改].meeting'][] = 'Meetings::meeting_ACL';
$config['is_allowed_to[删除].meeting'][] = 'Meetings::meeting_ACL';
$config['is_allowed_to[添加公告].meeting'][] = 'Meetings::meeting_ACL';
$config['is_allowed_to[修改公告].meeting'][] = 'Meetings::meeting_ACL';
$config['is_allowed_to[删除公告].meeting'][] = 'Meetings::meeting_ACL';
$config['is_allowed_to[管理授权].meeting'][] = 'Meetings::meeting_ACL';
$config['is_allowed_to[管理预约].meeting'][] = 'Meetings::meeting_ACL';
$config['is_allowed_to[查看所有会议室预约].meeting'][] = 'Meetings::meeting_ACL';

//ME_Reserv模块权限，绑定
$config['is_allowed_to[添加].cal_component'][] = 'ME_Reserv_Access::add_is_allowed';
$config['is_allowed_to[删除].cal_component'][] = 'ME_Reserv_Access::delete_is_allowed';
$config['is_allowed_to[修改].cal_component'][] = 'ME_Reserv_Access::edit_is_allowed';
$config['is_allowed_to[查看].cal_component'][] = 'ME_Reserv_Access::view_is_allowed';

$config['is_allowed_to[添加事件].calendar'][] = 'ME_Reserv_Access::add_event_is_allowed';
$config['is_allowed_to[修改事件].calendar'][] = 'ME_Reserv_Access::edit_event_is_allowed';
$config['is_allowed_to[列表事件].calendar'][] = 'ME_Reserv_Access::list_event_is_allowed';

$config['view[calendar/component_form].prerender'][] = 'ME_Reserv::prerender_component';
$config['view[calendar/component_info].prerender'][] = 'ME_Reserv::prerender_component';
$config['cal_component_model.call.check_overlap'][] = 'ME_Reserv::is_check_overlap';
$config['cal_component_model.call.check_authorized'][] = 'ME_Reserv::is_check_authorized';
$config['cal_component_model.saved'][] = 'ME_Reserv::cal_component_saved';
$config['cal_component_model.deleted'][] = 'ME_Reserv::cal_component_deleted';
$config['calendar.component_content.render'][] = 'ME_Reserv::component_content_render';
$config['calendar.component.get_color'][] = 'ME_Reserv::cal_component_get_color';

//删除会议室
//$config['meeting_model.before_delete'][] = 'ME_Reserv::before_meeting_delete';
$config['calendar.components.get'][] = 'ME_Reserv::calendar_components_get';
$config['calendar.component_form.delete'][] = 'ME_Reserv::component_form_delete';
$config['calendar.component_form.submit'][] = 'ME_Reserv::component_form_submit';
$config['calendar.component_form.post_submit'][] = 'ME_Reserv::component_form_post_submit';

$config['newsletter.view'][] = 'Meetings::newsletter_view';
$config['schedule.component_info'][] = 'ME_Reserv::schedule_component_info';
$config['calendar.list_empty_message'][] = 'ME_Reserv::empty_meeting_reserv_message';

$config['operate_door_is_allowed'][] = 'ME_Reserv::operate_door_is_allowed';

$config['component.notice'][] = 'ME_Reserv::notice';
