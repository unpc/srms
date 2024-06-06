<?php

//仪器修改tab进行绑定
$config['equipment.charge.edit.tab'][] = 'EQ_Charge_Expand::charge_edit_content_tabs';

//创建model
$config['eq_charge_model.saved'][] = 'EQ_Charge_Expand::charge_saved';

//删除model
$config['eq_charge_model.deleted'][] = 'EQ_Charge_Expand::charge_deleted';

//打印
$config['view[eq_charge:print_charges_table/data/minimum_fee].postrender'] = 'EQ_Charge_Expand::charge_print_minimum';
$config['view[eq_charge:print_charges_table/data/subsidy_fee].postrender'] = 'EQ_Charge_Expand::charge_print_subsidy';
$config['view[eq_charge:print_charges_table/data/expend_fee].postrender']  = 'EQ_Charge_Expand::charge_print_expend';

// 导出
$config['eq_charge.export_columns'] = 'EQ_Charge_Expand::charge_export';
