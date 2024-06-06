<?php

//$config['controller[admin/index].ready'][] = 'Cers::setup_admin';

// $config['controller[!equipments/equipment/edit].ready'][] = 'Cers::equipment_setup';

$config['equipment[edit].view'][] = 'Cers::equipment_edit_info_view';

$config['equipment[edit].view_diolog'][] = 'Cers::equipment_edit_view';

$config['equipment[edit].post_submit_validate'][] = 'Cers::equipment_post_submit_validate';

$config['equipment[add].post_submit_validate'][] = 'Cers::equipment_post_submit_validate';

$config['equipment[edit].post_submit'][] = 'Cers::equipment_post_submit';

$config['equipment[add].post_submit'][] = 'Cers::equipment_post_submit';


$config['is_allowed_to[管理].cers'][] = 'Cers_Access::cers_ACL';

$config['module[cers].is_accessible'][] = 'Cers_Access::is_accessible';
