<?php

$config['is_allowed_to[审核].eq_charge'][] = 'Charge_Confirm_Access::charge_ACL';
$config['is_allowed_to[确认].eq_charge'][] = 'Charge_Confirm_Access::charge_ACL';
$config['is_allowed_to[打印].eq_charge'][] = 'Charge_Confirm_Access::charge_ACL';

$config['eq_sample_model.call.is_locked'][] = ['callback' => 'Charge_Confirm::object_is_locked', 'weight' => -5];
$config['eq_reserv_model.call.is_locked'][] = ['callback' => 'Charge_Confirm::object_is_locked', 'weight' => -5];
$config['eq_record_model.call.is_locked'][] = ['callback' => 'Charge_Confirm::object_is_locked', 'weight' => -5];

$config['index_charges.table_list.columns'][] = 'Charge_Confirm::charges_table_list_columns';
$config['index_charges.table_list.row'][] = 'Charge_Confirm::charges_table_list_row';

$config['lab_charges.table_list.columns'][] = 'Charge_Confirm::charges_table_list_columns';
$config['lab_charges.table_list.row'][] = 'Charge_Confirm::charges_table_list_row';
