<?php

// 初始化模块保存人员类型
$config['create_orm_tables'][] = 'People::create_orm_tables';
//模块默认的 目前成员/过期成员/访客 是否显示相关的链接和权限设置
$config['controller[admin/index].ready'][] = 'People_Admin::setup';
$config['controller[admin/index].ready'][] = 'People_Support::setup';
$config['controller[!update].ready'][] = 'People::setup';
$config['controller[!people/profile].ready'][] = 'People::setup_profile_page';
$config['controller[*].ready'][] = 'People::accessible_controller';
$config['controller[*].ready'][] = ['callback' => 'Switchrole::switch_role', 'weight' => 200];

$config['user_model.updating'][] = 'People::get_update_parameter';
$config['user_model.update.message'][] = 'People::get_update_message';
$config['user_model.update.message_view'][] = 'People::get_update_message_view';

$config['is_allowed_to[修改].user'][] = 'People::user_ACL';
$config['is_allowed_to[添加].user'][] = 'People::user_ACL';
$config['is_allowed_to[删除].user'][] = 'People::user_ACL';
$config['is_allowed_to[查看].user'][] = 'People::user_ACL';
$config['is_allowed_to[管理角色].user'][] = 'People::user_ACL';
$config['is_allowed_to[查看角色].user'][] = 'People::user_ACL';
$config['is_allowed_to[修改组织机构].user'][] = 'People::user_ACL';
$config['is_allowed_to[导出].user'][] = 'People::user_ACL';
$config['is_allowed_to[隐藏].user'][] = 'People::user_ACL';
$config['is_allowed_to[激活].user'][] = 'People::user_ACL';
$config['is_allowed_to[查看建立者].user'][] = 'People::user_ACL';
$config['is_allowed_to[查看审批者].user'][] = 'People::user_ACL';
$config['is_allowed_to[查看联系方式].user'][] = 'People::user_ACL';
$config['is_allowed_to[查看登录账号].user'][] = 'People::user_ACL';

/*
NO.TASK#274(guoping.zhang@2010.11.24)
people新权限规则设置
*/
$config['is_allowed_to[上传文件].user'][] = 'People::operate_attachment_is_allowed';
$config['is_allowed_to[删除文件].user'][] = 'People::operate_attachment_is_allowed';
$config['is_allowed_to[下载文件].user'][] = 'People::operate_attachment_is_allowed';
$config['is_allowed_to[修改文件].user'][] = 'People::operate_attachment_is_allowed';
$config['is_allowed_to[列表文件].user'][] = 'People::operate_attachment_is_allowed';
$config['is_allowed_to[添加目录].user'][] = 'People::operate_attachment_is_allowed';
$config['is_allowed_to[修改目录].user'][] = 'People::operate_attachment_is_allowed';
$config['is_allowed_to[删除目录].user'][] = 'People::operate_attachment_is_allowed';
$config['is_allowed_to[列表关注].user'][] = 'People::operate_follow_is_allowed';
$config['is_allowed_to[列表关注的用户].user'][] = 'People::operate_follow_is_allowed';
$config['is_allowed_to[关注].user'][] = 'People::operate_follow_is_allowed';
$config['is_allowed_to[取消关注].user'][] = 'People::operate_follow_is_allowed';

$config['people.get.roles'][] = 'People::get_all_roles';
$config['user_model.saved'][] = 'People::on_user_saved';
$config['user_model.before_save'][] = 'People::user_before_save';
$config['orm_model.before_save'][] = 'People::update_name_abbr';
$config['eq_reserv.calendar.people'][] = 'People::eq_reserv_calendar_people_link';
$config['get.user.simple.info'][] = 'People::get_user_simple_info';
$config['message.send.way.view'][] = 'People::message_send_way_view';
$config['message.send.way.submit'][] = 'People::message_send_way_submit';
$config['newsletter.get_contents[extra]'][] = 'People::people_newsletter_content';

//人员权限
$config['user_model.perms.enumerates'][] = 'People::on_enumerate_user_perms';
//检查用户是否为激活用户
$config['user_model.call.is_active'][] = 'People::user_is_active';
$config['user_model.call.get_binding_email'][] = 'People::get_binding_email';
$config['user_model.call.get_binding_phone'][] = 'People::get_binding_phone';
$config['user_model.call.hideSidebar'][] = 'People::user_hide_sidebar';
$config['user_model.call.home_url'][] = 'People::home_url';
$config['user_model.call.access'][] = 'People::access';
$config['user_model.call.default_group'][] = 'People::default_group';

// 个人门户对接hook
$config['application.component.views'][] = 'People_Com::views';
$config['application.component.view.userStatus'][] = 'People_Com::view_userStatus';
$config['application.component.view.userApproval'][] = 'People_Com::view_userApproval';

$config['role.available'] = 'People::role_available';

$config['auth.logout'][] = 'Switchrole::unset_switch_role';


$config['api.v1.user-info.GET'][] = 'User_API::user_info_get';
$config['api.v1.user-card.GET'][] = 'User_API::user_card_get';

$config['get_user_by_username'][] = 'User_API::get_user_by_id';
$config['get_user_by_username'][] = 'User_API::get_user_by_username';
$config['api.v1.users.GET'][] = 'User_API::users_get';
$config['api.v1.user.GET'][] = 'User_API::user_get';
$config['user.api.v1.list.GET'][] = 'User_API::users_get';
$config['user.api.v1.GET'][] = 'User_API::user_get';

$config['user_signup_requires'][] = 'People_Support::signup_user_requoires';