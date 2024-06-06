<?php
// 系统设置-仪器管理-黑名单设置
$config['controller[admin/index].ready'][] = 'EQ_Ban_Admin::setup';
$config['is_allowed_to[修改黑名单设置].equipment'][] = 'EQ_Ban_Access::equipment_banned_ACL';

$config['other_notification.add.logs'][] = 'EQ_Ban_Admin::eq_ban_add_logs';
$config['admin.equipments.notification_configs'][] = 'EQ_Ban_Admin::eq_ban_add_notification';

// 设置用户被加入仪器黑名单的提醒消息
$config['controller[!equipments/equipment/edit].ready'][] = 'Eq_Ban_Message::edit_banned';
// notification
$config['notification.get_template'][] = 'Eq_Ban_Message::get_template';
$config['notification.get_template_name'][] = 'Eq_Ban_Message::get_template_name';

$config['equipment_model.call.cannot_access'][] = ['callback' => 'EQ_Ban::banned_cannot_use', 'weight' => -2];

//对eq_banned黑名单控制处理的hook
$config['is_allowed_to[查看全局].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';
$config['is_allowed_to[添加全局].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';
$config['is_allowed_to[编辑全局].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';
$config['is_allowed_to[查看机构].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';
$config['is_allowed_to[添加机构].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';
$config['is_allowed_to[编辑机构].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';
$config['is_allowed_to[查看仪器].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';
$config['is_allowed_to[添加仪器].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';
$config['is_allowed_to[编辑仪器].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';
$config['is_allowed_to[编辑仪器违规记录].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';

$config['is_allowed_to[查看违规记录].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';
$config['is_allowed_to[查看下属机构的违规记录].eq_banned'][] = 'EQ_Ban_Access::banned_ACL';

$config['user.get.labs.selector'][] = 'EQ_Ban::user_get_labs_selector';

$config['module[eq_ban].is_accessible'][] = 'EQ_Ban_Access::is_accessible';

$config['eq_banned_model.before_save'][] = 'EQ_Ban::update_abbr';

$config['user_model.deleted'][] = 'EQ_Ban::user_deleted';

//黑名单历史记录留存
$config['eq_banned_model.deleted'][] = 'EQ_Ban::record_save';
$config['feedback_no_project_view'][] = ['callback' => 'EQ_Ban::feedback_no_project_view', 'weight' => 3];;

$config['view[calendar/permission_check].prerender'][] = 'EQ_Ban::reserv_permission_check';
