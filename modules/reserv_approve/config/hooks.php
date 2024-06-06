<?php
$config['is_allowed_to[修改].cal_component'][] = ['callback'=> 'Reserv_Approve::modify_is_allowed', 'weight'=> -5];
$config['is_allowed_to[删除].cal_component'][] = ['callback'=> 'Reserv_Approve::delete_is_allowed', 'weight'=> -5];

$config['eq_reserv_model.saved'][] = ['callback'=> 'Reserv_Approve::on_eq_reserv_saved', 'weight'=> 999];
$config['reserv_approve_model.saved'][] = ['callback'=> 'Reserv_Approve::on_reserv_approve_saved', 'weight'=> 999];

$config['is_allowed_to[查看].reserv_approve'][] = 'Reserv_Approve_Access::approve_ACL';
$config['is_allowed_to[查看全部].reserv_approve'][] = 'Reserv_Approve_Access::approve_ACL';
$config['is_allowed_to[撤回].reserv_approve'][] = 'Reserv_Approve_Access::approve_ACL';
$config['is_allowed_to[审核].reserv_approve'][] = 'Reserv_Approve_Access::approve_ACL';
$config['is_allowed_to[机主审核].reserv_approve'][] = 'Reserv_Approve_Access::approve_ACL';
$config['is_allowed_to[PI审核].reserv_approve'][] = 'Reserv_Approve_Access::approve_ACL';
$config['is_allowed_to[驳回].reserv_approve'][] = 'Reserv_Approve_Access::approve_ACL';

$config['component_info.prerender.extra'][] = 'Reserv_Approve::component_info_extra';

// 合并预约判断的额外规则
$config['eq_reserv.merge_reserv.extra'][] = 'Reserv_Approve::merge_reserv';

// 没有通过预约审批的无法直接上机
$config['equipment_model.call.cannot_access'][] = 'Reserv_Approve::cannot_access_equipment';