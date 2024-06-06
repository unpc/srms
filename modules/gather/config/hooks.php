<?php

$config['controller[!equipments].ready'][] = ['callback' => 'Gather_Equipment::setup', 'weight' => 0];

$config['equipment.table_list.columns'][] = 'Gather_Equipment::equipment_list_columns';
$config['equipment.table_list.row'][]     = 'Gather_Equipment::equipment_list_row';

/* $config['eq_record.list.columns'][] = 'Gather_Equipment::equipment_list_columns';
$config['eq_record.list.row'][]     = 'Gather_Equipment::equipment_list_row';

$config['eq_sample.table_list.columns'][] = 'Gather_Equipment::equipment_list_columns';
$config['eq_sample.table_list.row'][]     = 'Gather_Equipment::equipment_list_row';

$config['eq_reserv.table_list.columns'][] = 'Gather_Equipment::equipment_list_columns';
$config['eq_reserv.table_list.row'][]     = 'Gather_Equipment::equipment_list_row'; */

$config['equipment.extra.selector'][]       = 'Gather_Equipment::equipment_extra_selector';
$config['eq_reserv.search.filter.submit'][] = 'Gather_Equipment::eq_reserv_extra_selector';
$config['eq_sample.search.filter.submit'][] = 'Gather_Equipment::eq_sample_extra_selector';
$config['eq_record.search_filter.submit'][] = 'Gather_Equipment::eq_record_extra_selector';

$config['equipments.get.export.record.columns'][]    = 'Gather_Equipment::get_export_record_columns';
$config['equipments.export_columns.eq_record.new'][] = 'Gather_Equipment::export_record_columns';
$config['eq_sample.extra.export_columns'][]          = 'Gather_Equipment::export_record_columns';
$config['eq_reserv.extra.export_columns'][]          = 'Gather_Equipment::export_record_columns';

/* $config['eq_record.list.columns'][] = 'Gather_Equipment::eq_record_list_columns';
$config['eq_record.list.row'][]     = 'Gather_Equipment::eq_record_list_row';

$config['eq_sample.table_list.columns'][] = 'Gather_Equipment::eq_sample_list_columns';
$config['eq_sample.table_list.row'][]     = 'Gather_Equipment::eq_sample_list_row';

$config['eq_reserv.table_list.columns'][] = 'Gather_Equipment::eq_reserv_list_columns';
$config['eq_reserv.table_list.row'][]     = 'Gather_Equipment::eq_reserv_list_row'; */

//人员所属站点
$config['people.table_list.columns'][] = 'Gather_Equipment::people_list_columns';
$config['people.table_list.row'][] = 'Gather_Equipment::people_list_row';
$config['people.index.search.submit'][] = 'Gather_Equipment::people_extra_selector';

// $config['lab.table_list.columns'][] = 'Gather_Equipment::lab_list_columns';
// $config['lab.table_list.row'][] = 'Gather_Equipment::lab_list_row';
// $config['lab.index.search.submit'][] = 'Gather_Equipment::lab_extra_selector';

$config['user.extra.keys'][] = 'Gather_User::user_extra_keys';

$config['equipment.info.api.extra'][] = 'Whu_Gather_Equipments::info_api_extra';