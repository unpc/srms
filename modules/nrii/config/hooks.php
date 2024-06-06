<?php

$config['soap.jsx_to_xml.response'][] = 'Soap_Helper::replace_jsx_to_xml_data';

$config['is_allowed_to[管理].nrii'][] = 'Nrii_Access::operate_nrii_is_allowed';

$config['is_allowed_to[编辑].nrii_equipment'][] = 'Nrii_Access::operate_nrii_equipment_is_allowed';
$config['is_allowed_to[上传至科技部].nrii_equipment'][] = 'Nrii_Access::operate_nrii_equipment_is_allowed';
$config['is_allowed_to[审核].nrii_equipment'][] = 'Nrii_Access::operate_nrii_equipment_is_allowed';

$config['module[nrii].is_accessible'][] = 'Nrii_Access::is_accessible';


$config['equipment[edit].view'][] = ['callback' => 'Nrii::equipment_edit_info_view', 'weight' => -999];

$config['equipment[edit].view_diolog'][] = ['callback' => 'Nrii::equipment_edit_info_view_dialog', 'weight' => -999];

$config['equipment[edit].post_submit_validate'][] = 'Nrii::equipment_post_submit_validate';

$config['equipment[add].post_submit_validate'][] = 'Nrii::equipment_post_submit_validate';

$config['equipment[edit].post_submit'][] = 'Nrii::equipment_post_submit';

$config['equipment[add].post_submit'][] = 'Nrii::equipment_post_submit';