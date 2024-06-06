<?php

// equipment 相关
$config['equipment_model.saved'][] = 'Yiqikong_Equipment::on_equipment_saved';
$config['equipment_model.deleted'][] = 'Yiqikong_Equipment::on_equipment_deleted'; // 删除仪器时推送，仪器控directory删除
$config['ue_training_model.saved'][] = 'YiQiKong_Equipment::on_training_saved';
$config['ue_training_model.deleted'][] = 'YiQiKong_Equipment::on_training_deleted';
$config['eq_status_model.saved'][] = 'YiQiKong_Equipment::on_status_saved';
$config['eq_announce_model.saved'][] = 'YiQiKong_Equipment::on_eq_announce_saved';
$config['eq_announce_model.deleted'][] = 'YiQiKong_Equipment::on_eq_announce_deleted';
$config['user.eq_announce.connect'][] = 'YiQiKong_Equipment::on_user_eq_announce_connect';
$config['user.eq_announce.disconnect'][] = 'YiQiKong_Equipment::on_user_eq_announce_disconnect';


//eq_reserv 相关
$config['reserv.tab.content.validate'][] = 'Yiqikong_Reserv::reserv_tab_content_validate';
$config['eq_reserv_model.saved'][] = 'Yiqikong_Reserv::on_eq_reserv_saved';
$config['eq_reserv_model.deleted'][] = 'Yiqikong_Reserv::on_eq_reserv_deleted';

//eq_sample 相关
$config['sample.tab.content.validate'][] = 'Yiqikong_Sample::sample_tab_content_validate';
$config['eq_sample_model.saved'][] = 'Yiqikong_Sample::on_eq_sample_saved';
$config['eq_sample_model.deleted'][] = 'Yiqikong_Sample::on_eq_sample_deleted';
$config['eq_sample_eq_record.connect'][] = 'Yiqikong_Sample::on_eq_sample_eq_record_connect';
$config['eq_sample_eq_record.disconnect'][] = 'Yiqikong_Sample::on_eq_sample_eq_record_disconnect';


//eq_charge 相关
$config['eq_charge_model.saved'][] = 'Yiqikong_Charge::on_eq_charge_saved';
$config['eq_charge_model.saved'][] = 'Yiqikong_Charge::on_eq_charge_saved_app';
$config['eq_charge_model.deleted'][] = 'Yiqikong_Charge::on_eq_charge_deleted';
$config['eq_charge_model.deleted'][] = 'Yiqikong_Charge::on_eq_charge_deleted_app';

//eq_record 相关
$config['eq_record_model.saved'][] = ['callback' => 'Yiqikong_Record::on_eq_record_saved', 'weight' => 999];
$config['eq_record_model.deleted'][] = 'Yiqikong_Record::on_eq_record_deleted';
$config['eq_banned_model.saved'][] = 'Yiqikong_ban::on_eq_banned_saved';

// extra相关
$config['extra_value_model.saved'][] = 'Yiqikong_Extra::on_extra_value_saved';

// user相关
$config['user.links'][] = 'Yiqikong_User::user_links';
$config['follow_model.saved'][] = 'Yiqikong_User::on_follow_saved';
$config['follow_model.deleted'][] = 'Yiqikong_User::on_follow_deleted';
$config['user.extra.keys'][] = "Yiqikong_User::user_extra_keys";

$config['user_model.saved'][] = 'Control_Baratheon::on_user_saved';
$config['user_model.deleted'][] = 'Control_Baratheon::on_user_deleted';


//tag相关
$config['orm_model.saved'][] = 'Control_Arryn::on_tag_saved';
$config['orm_model.deleted'][] = 'Control_Arryn::on_tag_deleted';
$config['user_tag_equipment_user_tags.connect'][] = 'Control_Arryn::on_user_tag_connect';
$config['user_tag_equipment_user_tags.disconnect'][] = 'Control_Arryn::on_user_tag_disconnect';


//用户角色
$config['user.after_role_change'][] = 'Control_Arryn::on_user_role_saved';
$config['after.role_perms_change'][] = 'Control_Arryn::on_user_role_perm_saved';

//lab相关
$config['lab_model.saved'][] = 'Control_Arryn::on_lab_saved';
$config['lab_model.deleted'][] = 'Control_Arryn::on_lab_deleted';

//billing相关
$config['billing_transaction_model.saved'][] = 'Control_Lannister::on_transaction_saved';
$config['billing_transaction_model.deleted'][] = 'Control_Lannister::on_transaction_deleted';
$config['billing_account_model.saved'][] = 'Control_Lannister::on_account_saved';
$config['billing_account_model.deleted'][] = 'Control_Lannister::on_account_deleted';
$config['billing_department_model.saved'][] = 'Control_Lannister::on_department_saved';
$config['billing_department_model.deleted'][] = 'Control_Lannister::on_department_deleted';

//door
$config['door_model.saved'][] = 'Control_Equipment::on_door_saved';
$config['door_model.deleted'][] = 'Control_Equipment::on_door_deleted';
$config['user_door.connect'][] = 'Control_Equipment::on_user_door_connect';
$config['user_door.disconnect'][] = 'Control_Equipment::on_user_door_disconnect';

//vidmon
$config['vidcam_model.saved'][] = 'Control_Equipment::on_vidcam_saved';
$config['vidcam_model.deleted'][] = 'Control_Equipment::on_vidcam_deleted';
$config['vidcam_equipment.connect'][] = 'Control_Equipment::on_vidcam_equipment_connect';
$config['vidcam_equipment.disconnect'][] = 'Control_Equipment::on_vidcam_equipment_disconnect';
$config['vidcam_user.connect'][] = 'Control_Equipment::on_user_vidcam_connect';
$config['vidcam_user.disconnect'][] = 'Control_Equipment::on_user_vidcam_disconnect';


$config['api.v1.door-auth.GET'][] = 'Control_Equipment::door_auth';

$config['equipment.reserv.extra.fields.value'][] = 'Control_Equipment::get_feedback_schema';//获取反馈表单，钩子名用之前的了。

$config['user_model.call.has_bind_app'][] = 'Yiqikong_User::user_has_bind_app';

$config['yiqikong.object.links[eq_reserv]'][] = 'Yiqikong_Reserv::links';
$config['yiqikong.object.links[eq_sample]'][] = 'Yiqikong_Sample::links';
$config['yiqikong.object.links[eq_record]'][] = 'Yiqikong_Record::links';

//approval 预约审批相关
$config['approval_model.saved'][] = 'Control_Equipment_Approval::on_approval_saved';
$config['orm_model.saved'][] = ['callback' =>'Control_Equipment_Approval::orm_model_saved', 'weight' => 999];
