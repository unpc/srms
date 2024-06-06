<?php

$config['system.ready'][] = 'EQ_Sample_Model::setup';

//初始化
$config['controller[!people/profile].ready']['old_approval_flow'] = 'Approval::setup_view';
// 新建数据进入审批流程 approval->create()
$config['orm_model.saved'][] = 'Approval::orm_model_saved';
// 编辑数据自动pass approval->pass()
$config['orm_model.saved'][] = 'Approval::orm_model_saved_pass';
$config['approval_model.saved'][] = 'Approval::on_approval_saved';

//配置预约审核勾选框
$config['eq_reserv.requirement.extra.view'][] = 'Approval::eq_reserv_requirement_extra_view';
$config['eq_reserv.need_approval'][] = 'Approval::eq_reserv_need_approval';
$config['equipment[edit].reserv.post_submit'][] = 'Approval::eq_reserv_need_approval';
$config['equipment.accept_reserv.change'][] = 'Approval::equipment_accept_reserv_change';
$config['view[calendar/component_show].prerender'][] = 'Approval::prerender_component';

//预约删除和保存时的关联操作
$config['model_approval.after.reject'][] = 'Approval::model_approval_after_reject';

$config['approve_pi.message'][] = 'Approval_Message::approval_message_approve_pi_to_user';
$config['approve_incharge.message'][] = 'Approval_Message::approval_message_approve_incharge_to_user';
$config['done.message'][] = 'Approval_Message::approval_message_pass_to_user';
$config['rejected.message'][] = 'Approval_Message::approval_message_reject_to_user';

$config['orm_model.deleted'][] = 'Approval::on_object_deleted';
$config['expired.message'][] = 'Approval_Message::expired_message_reject_to_user';
$config['approve_pi.message'][] = ['callback' => 'Approval_Message_PI::approval_message_approve_pi_to_user', 'weight' => -999];
$config['approve_incharge.message'][] = ['callback' => 'Approval_Message_PI::approval_message_approve_incharge_to_user', 'weight' => -999];

//日历模块显示的审核流程
$config['calendar_flow_status.approve_pi.str'][] = 'Approval::eq_reserv_approval_pi_str';
$config['calendar_flow_status.approve_incharge.str'][] = 'Approval::eq_reserv_approve_incharge_str';
$config['calendar_flow_status.done.str'][] = 'Approval::eq_reserv_done_str';
$config['is_allowed_to[修改].cal_component'][] = ['callback'=> 'Approval::modify_is_allowed', 'weight'=> -5];
$config['component_info.prerender.extra'][] = 'Approval::component_info_extra';

//权限 //针对flow配置进行权限判断 //增加新角色在此处增加权限
$config['user_model.call.can_approval'][] = 'Approval_Access::can_approval';
$config['approval.approve_pi.access'][] = 'Approval_Access::approve_pi_access';
$config['approval.approve_incharge.access'][] = 'Approval_Access::approve_incharge_access';
$config['approval.done.access'][] = 'Approval_Access::approve_done_access';
$config['approval.rejected.access'][] = 'Approval_Access::approve_rejected_access';
$config['approval.expired.access'][] = 'Approval_Access::approve_expired_access';
$config['approval.tab.access'][] = 'Approval_Access::approve_first_tab_access';//预约审核层页卡的权限（最外层的）

//预约审核查看状态的显示
$config['view_flow.approve_pi.str'][] = 'Approval::approval_view_approval_pi_str';
$config['view_flow.approve_incharge.str'][] = 'Approval::approval_view_approve_incharge_str';
$config['view_flow.done.str'][] = 'Approval::approval_view_done_str';
$config['view_flow.rejected.str'][] = 'Approval::approval_view_rejected_str';
$config['view_flow.expired.str'][] = 'Approval::approval_view_expired_str';

//预约审核列表selector
$config['pre_selector_role.approve_pi'][] = 'Approval::approval_approval_pi_pre_selector';
$config['pre_selector_role.approve_incharge'][] = 'Approval::approval_approve_incharge_pre_selector';
$config['pre_selector_role.done'][] = 'Approval::approval_done_pre_selector';
$config['pre_selector_role.rejected'][] = 'Approval::approval_reject_pre_selector';
$config['pre_selector_role.expired'][] = 'Approval::approval_expired_pre_selector';

$config['selector_role.approve_pi'][] = 'Approval::approval_approval_pi_selector';
$config['selector_role.approve_incharge'][] = 'Approval::approval_approve_incharge_selector';
$config['selector_role.done'][] = 'Approval::approval_done_selector';
$config['selector_role.rejected'][] = 'Approval::approval_reject_selector';
$config['selector_role.expired'][] = 'Approval::approval_expired_selector';

// 于课题组详情页，增加“预约送样审核”标签页，组内用户添加的预约/ 送样申请，显示在“预约送样审核”标签页。
$config['controller[!labs/lab/index].ready'][] = 'Approval_Flow_Lab::setup_approval_tab';
// 权限
$config['is_allowed_to[查看审核].lab'][] = 'Approval_Flow_Lab_Access::approval_ACL';

// 在负责仪器信息页，增加“预约审核”标签页
$config['controller[!equipments/equipment/index].ready'][] = 'Approval_Flow_Equipment::setup_approval_tab';
// 权限
$config['is_allowed_to[查看审核].equipment'][] = 'Approval_Flow_Equipment_Access::approval_ACL';

// 个人信息页增加 ”我的预约/送样审核“ 标签页
$config['controller[!people/profile].ready'][] = 'Approval_Flow_Mine::setup_approval_tab';

// 于课题组修改->”预约送样审核设置“ 处，增加”需要审核“勾选项
$config['lab.edit.secondary_tabs'][] = 'Approval_Flow_Lab::approval_config_tab';

// 课题组修改->”预约送样审核设置“ 后影响审批流程
$config['model_approval.create'][] = 'Approval_Flow::model_approval_create';
// 审核影响送样状态
$config['model_approval.after.pass'][] = 'Approval_Flow::eq_sample_approval_after_pass';
$config['model_approval.after.pass'][] = 'Approval_Flow::ue_training_approval_after_pass';
$config['model_approval.after.reject'][] = 'Approval_Flow::ue_training_approval_after_reject';

// 根据不同课题组/仪器勾选是否需要审批, 做审批处理
$config['approval_model.saved'][] = 'Approval_Flow::approval_model_saved';

$config['eq_sample_model.before_save'][] = 'Approval_Flow::eq_sample_model_before_save';

// 没有通过审批的无法直接上机
$config['equipment_model.call.cannot_access'][] = 'Approval_Flow::cannot_access_equipment';

$config['approval.pending.count'][] = 'Approval::pending_count';

