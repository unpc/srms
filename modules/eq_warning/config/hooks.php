<?php
$config['controller[admin/index].ready'][] = 'EQ_Warning_Admin::setup';
$config['admin.reminder.tab'][] = 'EQ_Warning_Admin::setup_reminder';
//预警设置
$config['controller[!equipments/equipment/edit].ready'][] = 'EQ_Warning::setup_edit';
// 使用预警设置
$config['is_allowed_to[修改预警设置].equipment'][] = 'EQ_Warning::equipment_ACL';
$config['is_allowed_to[锁定预警设置].equipment'][] = 'EQ_Warning::equipment_ACL';
$config['is_allowed_to[查看预警统计].equipment'][] = 'EQ_Warning::equipment_ACL';
// $config['controller[!equipments/equipment/index].ready'][] = 'EQ_Warning::setup_stat_view';
$config['layout_controller_after_call'][] = 'Notification_Modal_Send::layout_after_call';
// $config['login.save_locale'][] = 'EQ_Warning::check_offline';
// $config['login.save_locale'][] = 'Notification_Modal_Send::login_show_modal';