<?php
//耗材设置
$config['controller[!equipments/equipment/edit].ready'][] = 'Material::setup_edit';
//耗材计费设置
$config['extra.charge.setting.view'][] = ['callback' => 'Material::extra_charge_setting_view', 'weight' => 99];
$config['extra.charge.setting.content'][] = 'Material::extra_charge_setting_content';
$config['equipment.charge.edit.content.tabs'][] = 'Material::charge_edit_content_tabs';
$config['template[material_count].setting_view'][] = 'Material::template_material_count_setting_view';
//预约界面增加耗材视图 & 表单验证 & 保存
$config['eq_reserv.prerender.component'][] = 'Material_Reserv::eq_reserv_prerender_component';
$config['calendar.component_form.submit'][] = 'Material_Reserv::component_form_submit';
$config['eq_reserv.component.form.post.submit'][] = 'Material_Reserv::component_form_post_submit';
//送样添加/编辑页面增加视图 & 表单验证 & 保存
$config['eq_sample.prerender.add.form'][] = 'Material_Sample::eq_sample_prerender_add_form';
$config['eq_sample.prerender.edit.form'][] = 'Material_Sample::eq_sample_prerender_edit_form';
$config['extra.form.validate'][] = 'Material::extra_form_validate';//使用记录也是这个验证
$config['sample.form.submit'][] = 'Material_Sample::eq_sample_form_submit';
//使用记录页面视图
$config['eq_record.add_view'][] = 'Material_Record::record_edit_view';
$config['eq_record.edit_view'][] = 'Material_Record::record_edit_view';
$config['eq_record.edit_submit'][] = 'Material_Record::record_post_form_submit';
//仪器预约页面打印导出
$config['calendar.extra.export_columns'][] = 'Material_Export::calendar_extra_export_columns';
$config['calendar.extra.export_columns.checked'][] = 'Material_Export::calendar_extra_export_columns_checked';
$config['calendar.export_list_csv'][] = 'Material_Export::calendar_export_list_csv';
$config['eq_reserv.export_list_csv'][] = 'Material_Export::eq_reserv_export_csv';
//仪器送样页面打印导出
$config['eq_sample.extra.export_columns'][] = 'Material_Export::extra_export_columns';
$config['eq_sample.export_list_csv'][] = 'Material_Export::eq_sample_export_csv';
//仪器使用记录页面打印导出
$config['equipments.get.export.record.columns'][] = 'Material_Export::get_export_record_columns';
$config['equipments.export_columns.eq_record.new'][] = 'Material_Export::get_export_record_columns';
$config['eq_record.export_list_csv'][] = 'Material_Export::eq_record_export_list_csv';
//收费页面打印导出
$config['eq_charge_export.cloumns'][] = 'Material_Export::get_export_charge_columns';

//收费
$config['charge_lua_result.after.calculate_amount'][] = 'Material_Charge::after_calculate_amount';

//'耗材费'列
$config['eq_sample.table_list.columns'][] = 'Material_Sample::sample_table_list_columns';
$config['eq_sample.table_list.row'][] = 'Material_Sample::sample_table_list_row';
$config['eq_record.list.columns'][] = 'Material_Record::eq_record_list_columns';
$config['eq_record.list.row'][] = 'Material_Record::eq_record_list_row';
$config['lab_charges.table_list.columns'][] = 'Material_Charge::charges_table_columns';
$config['index_charges.table_list.columns'][] = 'Material_Charge::charges_table_columns';
$config['lab_charges.table_list.row'][] = 'Material_Charge::charges_table_list_row';
$config['index_charges.table_list.row'][] = 'Material_Charge::charges_table_list_row';

//收费确认
$config['eq_charge_confirm.extra.selector'] = 'Material_Charge::eq_charge_confirm_extra_selector';
$config['eq_charge_confirm.table_list.columns'][] = 'Material_Charge::charge_table_list_columns';
$config['eq_charge_confirm.table_list.row'][] = 'Material_Charge::charge_table_list_row';

$config['eq_charge.primary.content.selector'][] = 'Material_Charge::eq_charge_primary_content_selector';

//送样查看页
$config['sample.extra.print'][] = 'Material_Sample::sample_extra_print';