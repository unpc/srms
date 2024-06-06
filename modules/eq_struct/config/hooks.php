<?php

// 在 “系统设置->仪器管理”页增加 “仪器入账管理” 标签页，该页仅中心管理员及技术支持账号可见。
$config['admin.equipment.secondary_tabs'][] = 'Eq_Struct::secondary_tabs';

// 在仪器基本信息中增加机组
$config['equipment[add].post_submit_validate'][] = 'Eq_Struct::equipment_post_submit_validate';
$config['equipment[edit].post_submit_validate'][] = 'Eq_Struct::equipment_post_submit_validate';
$config['equipment[add].post_submit'][] = 'Eq_Struct::equipment_post_submit';
$config['equipment[edit].post_submit'][] = 'Eq_Struct::equipment_post_submit';

$config['is_allowed_to[修改仪器入账].equipment'][] = 'Eq_Struct::equipment_ACL';
