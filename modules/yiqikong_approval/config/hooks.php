<?php
$config['eq_reserv.requirement.extra.view_new'][] = 'Approval::eq_reserv_requirement_extra_view';
$config['equipment[edit].reserv.post_submit'][] = 'Approval::eq_reserv_need_approval';
$config['equipment.accept_reserv.change'][] = 'Approval::equipment_accept_reserv_change';
$config['controller[!people/profile].ready'][] = 'Approval::setup_view';
$config['controller[!equipments/equipment/index].ready'][] = 'Approval::setup_view';
$config['is_allowed_to[机主审核].approval'][] = 'Approval_Access::approval_ACL';
$config['eq_reserv_approval.create'][] = 'Approval::eq_reserv_approval_create';
$config['eq_reserv_approval.create.once'][] = 'Approval::eq_reserv_approval_create_once';
$config['eq_reserv_approval.after.pass'][] = 'Approval::eq_reserv_approval_after_pass';
$config['eq_reserv_approval.after.reject'][] = 'Approval::eq_reserv_approval_after_reject';
$config['is_allowed_to[修改].cal_component'][] = ['callback'=> 'Approval::modify_is_allowed', 'weight' => -5];
$config['component_info.prerender.extra'][] = 'Approval::component_info_extra';
$config['orm_model.saved'][] = 'Approval::orm_model_saved';
$config['approval_model.saved'][] = 'Approval::on_approval_saved';
$config['eq_reserv_model.saved'][] = ['callback'=> 'Approval::on_eq_reserv_saved', 'weight'=> 999];
$config['eq_reserv_model.deleted'][] = 'Approval::on_eq_reserv_deleted';
$config['approval.pending.count'][] = 'Approval::pending_count';
$config['equipment_model.call.cannot_access'][] =['callback'=>'Approval_Access::cannot_access_equipment','weight'=>999];

$config['gpui.reserv_list.extra.info'][] = 'Approval_Help::gpui_reserv_list_extra_info';
