<?php

//预约界面增加经费卡号视图
$config['eq_reserv.prerender.component'][] = 'Billing_Later::eq_reserv_prerender_component';

//实验室获取grants信息
$config['lab_model.call.get_grants'][] = 'Billing_Later::get_lab_grants';

//预约表单提交时候增加预约的grant属性
$config['eq_reserv.component.form.post.submit'][] = 'Billing_Later::component_form_post_submit';

//仪器送样添加/编辑页面增加视图
$config['eq_sample.prerender.add.form'][] = 'Billing_Later::eq_sample_prerender_add_form';
$config['eq_sample.prerender.edit.form'][] = 'Billing_Later::eq_sample_prerender_edit_form';

//送样表单提交时候增加送样的grant属性
$config['sample.form.submit'][] = 'Billing_Later::eq_sample_form_submit';

//计费列表额外数据显示
$config['index_charges.table_list.columns'][] = 'Billing_Later::charges_table_list_columns';
$config['index_charges.table_list.row'][] = 'Billing_Later::charges_table_list_row';
$config['lab_charges.table_list.columns'][] = 'Billing_Later::charges_table_list_columns';
$config['lab_charges.table_list.row'][] = 'Billing_Later::charges_table_list_row';

// 所有仪器使用收费，按报销状态搜索
$config['eq_charge.selector.modify'][] = 'Billing_Later::eq_charge_selector_modify';
