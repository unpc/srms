<?php
$config['module[capability].is_accessible'][] = 'Capability_Access::is_accessible';

$config['is_allowed_to[列表绩效申报].capability_equipment_task'][] = 'Capability_Access::equipment_ACL';
$config['is_allowed_to[审批绩效申报].capability_equipment_task'][] = 'Capability_Access::equipment_ACL';
$config['is_allowed_to[绩效审批].capability_equipment_task'][] = 'Capability_Access::equipment_ACL';
$config['is_allowed_to[效益填报].capability_equipment_task'][] = 'Capability_Access::equipment_ACL';
$config['is_allowed_to[列表效益填报].capability_equipment_task'][] = 'Capability_Access::equipment_ACL';
