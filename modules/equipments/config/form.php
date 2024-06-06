<?php
$config['equipment_add']['requires'] = [
    'name'=>TRUE,
    'incharges'=>TRUE,
    'contacts'=>TRUE,
];

$config['equipment_add']['disables'] = [
    'name'=>TRUE,
    'en_name'=>TRUE,
    'model_no'=>TRUE,
    'specification'=>TRUE,
    'price'=>TRUE,
    'manu_at'=>TRUE,
    'manufacturer'=>TRUE,
    'manu_date'=>TRUE,
    'purchased_date'=>TRUE,
    'atime'=>TRUE,
    'group_id'=>TRUE,
    'cat_no'=>TRUE,
    'ref_no'=>TRUE,
    'location'=>TRUE,
    'tech_specs'=>TRUE,
    'features'=>TRUE,
    'configs'=>TRUE,
    'open_reserv'=>TRUE,
    'charge_info'=>TRUE,
    'yiqikong_share'=>TRUE,
    'incharges'=>TRUE,
    'contacts'=>TRUE,
    'phone'=>TRUE,
    'email'=>TRUE,
    'tag'=>TRUE,
    'open_reserv'=>TRUE,
    'charge_info'=>TRUE,
];

$config['equipment_edit'] = &$config['equipment_add'];
