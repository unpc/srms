<?php
$config['controller[!equipments/equipment/index].ready'][] = ['callback' => 'EQ_Maintain::setup_equipment', 'weight' => 0];

$config['is_allowed_to[查看维修记录].equipment'][] = 'EQ_Maintain_Access::equipment_maintain_ACL';
$config['is_allowed_to[修改维修记录].equipment'][] = 'EQ_Maintain_Access::equipment_maintain_ACL';
