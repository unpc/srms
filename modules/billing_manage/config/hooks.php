<?php

$config['module[billing_manage_fund].is_accessible'][] = 'billing_manage::is_accessible_fund';
$config['module[billing_manage_transaction_fund].is_accessible'][] = 'billing_manage::is_accessible_transaction_fund';
$config['module[billing_manage_stat_platform].is_accessible'][] = 'billing_manage::is_accessible_stat_platform';
//预约界面增加经费卡号视图
$config['eq_reserv.prerender.component']['billing_manage'] = ['callback'=> 'billing_manage::eq_reserv_prerender_component', 'weight'=>1000];
//预约表单提交时候增加预约的grant属性
$config['eq_reserv.component.form.post.submit']['billing_manage'] = 'billing_manage::component_form_post_submit';

//仪器送样添加/编辑页面增加视图
$config['eq_sample.prerender.add.form']['billing_manage'] = 'billing_manage::eq_sample_prerender_add_form';
$config['eq_sample.prerender.edit.form']['billing_manage'] = 'billing_manage::eq_sample_prerender_edit_form';
//送样表单提交时候增加送样的grant属性
$config['sample.form.submit']['billing_manage'] = 'billing_manage::eq_sample_form_submit';
//表单提交时候的必填校验
$config['extra.form.validate']['billing_manage'] = 'billing_manage::extra_form_validate';


//使用记录反馈时增加视图
$config['extra.feedback.fields.view'][] = 'billing_manage::feedback_extra_view';
//使用记录反馈时
$config['feedback.form.submit'][] = 'billing_manage::feedback_form_submit';

$config['eq_charge_model.before_save'][] = 'billing_manage::eq_charge_before_save';
$config['eq_charge_model.saved'][] = 'billing_manage::eq_charge_saved';
$config['eq_charge_model.deleted'][] = 'billing_manage::on_charge_deleted';
$config['orm_model.saved'][] = 'billing_manage::orm_model_saved';


$config['equipment_model.call.cannot_be_reserved'][] = 'billing_manage::cannot_reserv_equipment';
$config['equipment_model.call.cannot_be_sampled'][] = 'billing_manage::cannot_sample_equipment';
// $config['view[calendar/permission_check].prerender'][] = 'billing_manage::reserv_permission_check';


$config['api.v1.billing-equipments.GET'][] = 'Billing_Manage_API::billing_equipments_get';

$config['equipment_model.call.billing_department'][] = 'billing_manage::equipment_billing_department';

