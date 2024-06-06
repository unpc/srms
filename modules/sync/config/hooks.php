<?php
// demo 配置, 如需在site中配置
// _r 表同步
// $config['user_lab.connect'][] = 'Sync_Publish::on_relationship_connect';
// $config['user_lab.disconnect'][] = 'Sync_Publish::on_relationship_disconnect';
// $config['user_equipment.connect'][] = 'Sync_Publish::on_relationship_connect';
// $config['user_equipment.disconnect'][] = 'Sync_Publish::on_relationship_disconnect';
// $config['role_perm.connect'][] = 'Sync_Publish::on_relationship_connect';
// $config['role_perm.disconnect'][] = 'Sync_Publish::on_relationship_disconnect';

$config['orm_model.before_save'][] = 'Sync_Publish::save_uuid';
$config['orm_model.saved'][] = 'Sync_Publish::orm_model_saved';
$config['orm_model.deleted'][] = 'Sync_Publish::orm_model_deleted';

$config['orm_model.call.url'][] = 'Sync_Utils::orm_model_call_url';

$config['is_allowed_to[删除].equipment'][] = ['callback' => 'Sync_Access::equipment_ACL', 'weight' => -999];
$config['is_allowed_to[删除].user'][] = ['callback'=>'Sync_Access::user_ACL','weight'=>-999];
$config['is_allowed_to[删除].lab'][] = ['callback'=>'Sync_Access::lab_ACL','weight'=>-999];

$config['equipment.api.extra'][] = 'Sync_Utils::equipment_api_extra';
$config['user.extra.keys'][] = "Sync_Utils::user_extra_keys";
$config['lab.extra.keys'][] = 'Sync_Utils::lab_extra_keys';
