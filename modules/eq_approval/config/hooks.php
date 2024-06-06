<?php


// add Tab控制
$config['lab.edit.tab'][] = "EQ_Approval::lab_edit_tab";
$config['lab.view.tab'][] = "EQ_Approval::lab_view_tab";
$config['profile.view.tab'][] = 'EQ_Approval::people_index_tab';

$config['controller[!people/profile/index].ready'][] = 'EQ_Approval::people_index_ready';


//is_allowd_to 权限控制
$config['is_allowed_to[修改审核].lab'][] = 'EQ_Approval_Access::operate_lab_is_allowed';
$config['is_allowed_to[查看审核].lab'][] = 'EQ_Approval_Access::operate_lab_is_allowed';

$config['is_allowed_to[查看].eq_voucher'][] = 'EQ_Approval_Access::operate_voucher_is_allowed';
$config['is_allowed_to[修改].eq_voucher'][] = 'EQ_Approval_Access::operate_voucher_is_allowed';
$config['is_allowed_to[删除].eq_voucher'][] = 'EQ_Approval_Access::operate_voucher_is_allowed';
$config['is_allowed_to[审批].eq_voucher'][] = 'EQ_Approval_Access::operate_voucher_is_allowed';


//sample hook
$config['eq_sample.prerender.add.form'][] = 'EQ_Approval_Hook::prerender_add_sample_form';
//因为没有其他的hook，暂时提交借用extra的hook
$config['extra.form.validate'][] = 'EQ_Approval_Hook::post_add_sample_form_validate';
$config['extra.form.post_submit'][] = 'EQ_Approval_Hook::sample_form_post_submit';
$config['eq_sample.prerender.edit.form'][] = 'EQ_Approval_Hook::prerender_edit_sample_form';
$config['eq_sample_model.before_delete'][] = 'EQ_Approval_Hook::before_eq_sample_delete';


//eq_reserv hook
$config['eq_reserv.prerender.component'][] = 'EQ_Approval_Hook::eq_reserv_prerender_component';
$config['calendar.component_form.submit'][] = 'EQ_Approval_Hook::component_form_submit';
$config['eq_reserv.component.form.post.submit'][] = 'EQ_Approval_Hook::component_form_post_submit';
$config['eq_reserv_model.before_delete'][] = 'EQ_Approval_Hook::before_eq_reserv_delete';

//user info hook
$config['people.info.short.picture'][] = 'EQ_Approval_Hook::short_picture_of_people';
$config['people.preview.short.picture'][] = 'EQ_Approval_Hook::short_preview_picture_of_people';

//user model call
$config['user_model.call.isShowVoucher'][] = 'EQ_Approval_Hook::isShowVoucher';
$config['user_model.call.isMayBeNeedVoucher'][] = 'EQ_Approval_Hook::isMayBeNeedVoucher';
$config['user_model.call.getCanUseVoucher'][] = 'EQ_Approval_Hook::getCanUseVoucher';

$config['user_model.call.isCouldSeeQuotaFromInfo'][] = 'EQ_Approval_Hook::isCouldSeeQuotaFromInfo';
$config['user_model.call.isCouldSeeQuotaFromPreviewInfo'][] = 'EQ_Approval_Hook::isCouldSeeQuotaFromPreviewInfo';
