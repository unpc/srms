<?php
$config['system.ready'][] = 'Platform::cache_platform';
$config['system.ready'][] = 'Platform::auth_login';
$config['system.ready'][] = 'Platform::set_lab_admin';

$config['controller[!servant/platform/index].ready'][] = 'Platform::setup_view';

$config['module[servant].is_accessible'][] = 'Servant_Access::is_accessible';

$config['is_allowed_to[管理].servant'][] = 'Servant_Access::servant_ACL';

$config['is_allowed_to[查看].platform'][] = 'Platform_Access::platform_ACL';
$config['is_allowed_to[添加].platform'][] = 'Platform_Access::platform_ACL';
$config['is_allowed_to[修改].platform'][] = 'Platform_Access::platform_ACL';
$config['is_allowed_to[删除].platform'][] = 'Platform_Access::platform_ACL';

$config['is_allowed_to[查看].user'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];
$config['is_allowed_to[添加].user'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];
$config['is_allowed_to[修改].user'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];
$config['is_allowed_to[删除].user'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];


$config['is_allowed_to[查看].lab'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];
$config['is_allowed_to[添加].lab'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];
$config['is_allowed_to[修改].lab'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];
$config['is_allowed_to[删除].lab'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];

$config['is_allowed_to[查看].equipment'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];
$config['is_allowed_to[添加].equipment'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];
$config['is_allowed_to[修改].equipment'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];
$config['is_allowed_to[删除].equipment'][] = ['weight' => -10, 'callback' => 'Platform_Access::object_ACL'];

$config['module[servant].is_accessible'][] = 'servant::is_accessible';

$config['platform_model.saved'][] = 'Platform::on_platform_saved';
$config['platform_model.before_delete'][] = 'Platform::on_platform_before_delete';

$config['role.set_roles'][] = ['weight' => -999, 'callback' => 'Platform::set_roles'];
$config['role_model.saved'][] = 'Platform::on_role_saved';

$config['login.extra_validate'][] = 'Platform::login_extra_validate';
$config['api.get_equipments_model.extend'][] = 'Platform::get_equipments_model_extend';

// 子站点新增人员、课题组、仪器时建立与子站点的关联
$config['user_model.saved'][] = 'Platform::on_object_saved';
$config['equipment_model.saved'][] = 'Platform::on_object_saved';
$config['lab_model.saved'][] = 'Platform::on_object_saved';

// 子站点管理员可以查看新统计
$config['module[eq_stat].is_accessible'][] = ['callback' => 'Platform::eq_stat_is_accessible', 'weight' => -999];
