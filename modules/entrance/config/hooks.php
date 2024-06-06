<?php

$config['door.in'][] = 'DC_Record::in_door_record';
$config['door.out'][] = 'DC_Record::out_door_record';
$config['controller[!people/profile].ready'][] = 'DC_Record::setup_profile';
$config['controller[!labs/lab/index].ready'][] = 'DC_Record::setup_lab';
$config['controller[!equipments/equipment/index].ready'][] = 'DC_Record::setup_equipment';

/*
NO.TASK#274(guoping.zhang@2010.11.27
门禁管理绑定权限
*/
$config['is_allowed_to[添加].door'][] = 'Door::operate_door_is_allowed';
$config['is_allowed_to[修改].door'][] = 'Door::operate_door_is_allowed';
$config['is_allowed_to[删除].door'][] = 'Door::operate_door_is_allowed';
$config['is_allowed_to[查看].door'][] = 'Door::operate_door_is_allowed';
$config['is_allowed_to[列表].door'][] = 'Door::operate_door_is_allowed';
$config['is_allowed_to[导出记录].door'][] = 'Door::operate_door_is_allowed';
$config['is_allowed_to[列表记录].door'][] = 'Door::operate_door_is_allowed';

$config['is_allowed_to[列表门禁记录].lab'][] = 'Door::operate_object_record_is_allowed';
$config['is_allowed_to[列表门禁记录].user'][] = 'Door::operate_object_record_is_allowed';
$config['is_allowed_to[列表门禁记录].equipment'][] = 'Door::operate_object_record_is_allowed';

$config['is_allowed_to[远程控制].door'][] = 'Door::operate_door_is_allowed';
$config['is_allowed_to[刷卡控制].door'][] = 'Door::operate_door_is_allowed';

$config['is_allowed_to[删除].dc_record'][] = 'Door::operate_record_is_allowed';
$config['door_model.call.cannot_access'][] = 'Door::cannot_access_door';
$config['newsletter.get_contents[security]'][] = 'Door::entrance_newsletter_content';

$config['door_model.saved'][] = 'Door::on_door_saved';
$config['people.card_no_changed'][] = 'Door::entrance_setting_sync';

$config['door_model.saved'][] = 'Door::on_door_saved';


$config['door_model.before_save'][] = 'Remote_Door::on_door_before_save';
$config['door_model.saved'][] = 'Remote_Door::on_door_saved';
$config['door_model.deleted'][] = 'Remote_Door::on_door_deleted';
$config['door_model.call.open_by_remote'][] = 'Remote_Door::open_by_remote';
$config['eq_reserv_model.saved'][] = 'Remote_Door_Rule::on_eq_reserv_saved';
$config['eq_reserv_model.deleted'][] = 'Remote_Door_Rule::on_eq_reserv_deleted';
$config['equipment_door.connect'][] = 'Remote_Door_Rule::on_equipment_door_connect';
$config['equipment_door.disconnect'][] = 'Remote_Door_Rule::on_equipment_door_disconnect';
$config['user_equipment.connect'][] = 'Remote_Door_Rule::on_user_equipment_connect';
$config['user_equipment.disconnect'][] = 'Remote_Door_Rule::on_user_equipment_disconnect';

$config['me_reserv_model.saved'][] = 'Remote_Door_Rule::on_me_reserv_saved';
$config['me_reserv_model.deleted'][] = 'Remote_Door_Rule::on_me_reserv_deleted';
$config['meeting_door.connect'][] = 'Remote_Door_Rule::on_meeting_door_connect';
$config['meeting_door.disconnect'][] = 'Remote_Door_Rule::on_meeting_door_disconnect';
$config['user_meeting.connect'][] = 'Remote_Door_Rule::on_user_meeting_connect';
$config['user_meeting.disconnect'][] = 'Remote_Door_Rule::on_user_meeting_disconnect';

$config['user_model.call.sync_card'][] = 'Remote_Door::sync_card';
$config['api.v1.current-user.GET'][] = 'iot_door::current_user';

$config['entrance.api.v1.middlewares.*'][] = 'API_Middlewares::gapperTokenAuth';
$config['entrance.api.v1.access-permission.GET'][] = 'Entrance_API::access_permission_get';