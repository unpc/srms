<?php
$config['operate_door_is_allowed'][] = 'EQ_Door::operate_door_is_allowed';

//仪器详细信息页面
$config['equipment.view.dashboard.sections'][] = 'EQ_Door::equipment_dashboard_sections';

$config['controller[!equipments/equipment/edit].ready'][] = 'EQ_Door::setup';
$config['controller[!entrance/door/index].ready'][] = 'EQ_Door::setup_door';
$config['controller[!meeting/index/edit].ready'][] = 'EQ_Door_Meeting::setup_meeting';
$config['controller[!people/profile].ready'][] = 'EQ_Door::setup_profile';

$config['is_allowed_to[关联门禁].equipment'][] = 'EQ_Door::operate_eq_door_is_allowed';
$config['is_allowed_to[关联门禁].meeting'][] = 'EQ_Door_Meeting::operate_eq_door_is_allowed';
