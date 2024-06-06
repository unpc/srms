<?php

// 初始化模块加载
$config['create_orm_tables'][] = 'Credit_Init::create_orm_tables';

// 个人详情tab页
$config['controller[!people/profile].ready'][] = 'Credit_Record::setup_view';

// 加载系统设置项
$config['controller[admin/index].ready'][] = 'Credit_Admin::setup';
$config['controller[admin/index].ready'][] = 'Credit_Support::setup';

// 用户saved时加载
$config['user_model.saved'][] = 'Credit::on_user_saved';
// 用户deleted时加载
$config['user_model.delete'][] = 'Credit::on_user_deleted';

// 用户deleted时加载
$config['user_model.deleted'][] = 'Credit::on_user_deleted';

// 触发计分规则
$config['trigger_scoring_rule'][] = 'Credit_Record::trigger_scoring_rule';
//删除预约触发计分规则
$config['calendar.component_form.after_delete'][] = 'Credit_Record::after_component_delete';
//预约状态修改后删除计分明细
$config['after_reserv_status_changed'][] = 'Credit_Record::after_reserv_status_changed';

// 总分不能自己生成, 必须挂勾每一条明细, 可以实现类似财务重算计费的功能
$config['credit_record_model.saved'][] = 'Credit::on_credit_record_saved';
$config['credit_record_model.before_delete'][] = 'Credit::credit_record_before_delete';

// 用户总分重新计算后, 根据新的总分触发奖惩规则
$config['credit_model.saved'][] = 'Credit::on_credit_saved';

// perms
$config['module[credit].is_accessible'][] = 'Credit_Access::is_accessible';
$config['is_allowed_to[查看信用记录].user'][] = 'Credit_Access::User_ACL';

//信用列表权限
$config['is_allowed_to[查看列表].credit'][] = 'Credit_Access::Credit_ACL';
$config['is_allowed_to[查看明细].credit'][] = 'Credit_Access::Credit_ACL';
$config['is_allowed_to[解禁].credit'][] = 'Credit_Access::Credit_ACL';
$config['is_allowed_to[添加记录].credit'][] = 'Credit_Access::Credit_ACL';
$config['is_allowed_to[导出].credit'][] = 'Credit_Access::Credit_ACL';
$config['is_allowed_to[打印].credit'][] = 'Credit_Access::Credit_ACL';

//信用明细权限
$config['is_allowed_to[查看列表].credit_record'][] = 'Credit_Access::Credit_Record_ACL';
$config['is_allowed_to[添加计分明细].credit_record'][] = 'Credit_Access::Credit_Record_ACL';
$config['is_allowed_to[导出].credit_record'][] = 'Credit_Access::Credit_Record_ACL';
$config['is_allowed_to[打印].credit_record'][] = 'Credit_Access::Credit_Record_ACL';
$config['is_allowed_to[统计数据].credit_record'][] = 'Credit_Access::Credit_Record_ACL';
//黑名单
$config['is_allowed_to[查看列表].eq_banned'][] = 'Credit_Access::Eq_Banned_ACL';
$config['is_allowed_to[查看仪器].eq_banned'][] = 'Credit_Access::Eq_Banned_ACL';
$config['is_allowed_to[查看平台].eq_banned'][] = 'Credit_Access::Eq_Banned_ACL';

//资格限制处理
$config['trigger_measures_ban'][] = 'Credit_Limit::ban';
$config['trigger_measures_unactive_user'][] = 'Credit_Limit::unactive_user';

//信用分过低禁止预约
$config['equipment_model.call.cannot_be_reserved'][] = 'Credit_Limit::can_not_reserv';
//信用分变更通知
$config['notification.after.credit_record_saved'][] = 'Credit_Notification::after_credit_record_saved';

// 用户由于信用分到达未激活分数变成未激活时 发消息
$config['notification.unactive_user'][] = 'Credit_Notification::measure_notification';
$config['notification.can_not_reserv'][] = 'Credit_Notification::measure_notification'; // 禁止预约仪器
$config['notification.ban'][] = 'Credit_Notification::measure_notification'; // 加入黑名单
$config['notification.send_msg'][] = 'Credit_Notification::measure_notification';

//解禁消息
$config['notification.thaw'][] = 'Credit_Notification::thaw';

//消息页面增加已读操作
$config['notification_read_setting.user.setting'][] = 'Credit_Notification::user_setting';

$config['view[calendar/permission_check].prerender'][] = 'Credit::reserv_permission_check';

$config['eq_banned_model.saved'][] = ['weight'=> 999,'callback'=>'Credit_Record::on_eq_banned_model_saved'];
$config['eq_banned_model.deleted'][] = ['weight'=> 999,'callback'=>'Credit_Record::on_eq_banned_model_deleted'];
