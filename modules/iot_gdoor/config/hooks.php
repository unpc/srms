<?php
//仪器设置修改
$config['controller[!equipments/equipment/edit].ready'][] = 'Iot_Gdoor::setup_equipment';
$config['is_allowed_to[关联门牌].equipment'][] = 'Iot_Gdoor::operate_gdoor_is_allowed';
$config['module[iot_gdoor].is_accessible'][] = 'Iot_Gdoor::is_accessible';

$config['iot_gdoor_model.call.access'][] = 'Iot_Gdoor_Access::access_iot_gdoor';
$config['iot_gdoor_model.call.access'][] = 'Iot_Gdoor_Access::access_iot_gdoor_meeting';
$config['equipment.view.dashboard.sections'][] = 'Iot_Gdoor::equipment_dashboard_sections';

// 可配置, 在lims没记录gapper_id的情况下, 可以根据iot-gdoor传来的gapper_id从gapper/gateway匹配用户
// $config['get_user_from_gapper_id'][] = 'Gapper_Gateway::get_user_from_gapper_id';

$config['eq_reserv_model.saved'][] = 'Iot_Gdoor_Rule::on_eq_reserv_saved';
$config['eq_reserv_model.deleted'][] = 'Iot_Gdoor_Rule::on_eq_reserv_deleted';

$config['iot_gdoor_equipment.connect'][] = 'Iot_Gdoor_Rule::on_iot_gdoor_equipment_connect';
$config['iot_gdoor_equipment.disconnect'][] = 'Iot_Gdoor_Rule::on_iot_gdoor_equipment_disconnect';
$config['user_equipment.connect'][] = 'Iot_Gdoor_Rule::on_user_equipment_connect';
$config['user_equipment.disconnect'][] = 'Iot_Gdoor_Rule::on_user_equipment_disconnect';
