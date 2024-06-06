<?php

$config['module[technical_service_record].is_accessible'][] = 'Technical_Service_Access::is_accessible';

$config['controller[admin/index].ready'][] = 'Technical_Service_Admin::setup';
$config['controller[!people/profile/index].ready'][] = 'Technical_Service::setup_profile';
$config['admin.import.tab'][] = "Technical_Service_Admin::import_tab";
$config['admin.index.tab.import_tab_can'][] = "Technical_Service_Admin::import_tab_params";
$config['import.layout'][] = ['callback' => 'Technical_Service_Admin::import_projects', 'weight' => '-1'];
$config['controller[!technical_service/extra/index].ready'][] = 'Technical_Service::setup_index';

$config['controller[!equipments/equipment/edit].ready'][] = 'Technical_Service::setup_equipment';
$config['template[sample_count].setting_view'][] = 'EQ_Charge_Script::template_sample_count_setting_view';

$config['extra.charge.setting.view'][] = 'Technical_Service::extra_charge_setting_view';
$config['extra.charge.setting.content'][] = 'Technical_Service::extra_charge_setting_content';
$config['equipment.charge.edit.content.tabs'][] = 'Technical_Service::charge_edit_content_tabs';

$config['service_apply_record_model.saved'][] = 'Technical_Service::service_apply_record_model_saved';

$config['eq_sample.prerender.add.form'][] = 'Technical_Service::eq_sample_prerender_add_form';
$config['eq_sample.prerender.edit.form'][] = 'Technical_Service::eq_sample_prerender_edit_form';
$config['extra.form.post_submit'][] = 'Technical_Service::sample_form_post_submit';
$config['extra.form.validate'][] = 'Technical_Service::extra_form_validate';
$config['apply.judge.balance'][] = 'Technical_Service::judge_balance';

$config['eq_sample.links'][] = 'Technical_Service::eq_sample_links_edit';

$config['service_apply.delete'][] = 'Technical_Service_Access::delete_apply_record';

$config['is_allowed_to[设置服务项目].equipment'][] = 'Technical_Service_Access::equipment_ACL';

$config['is_allowed_to[管理服务分类].technical_service'][] = 'Technical_Service_Access::user_ACL';
$config['is_allowed_to[管理服务项目].technical_service'][] = 'Technical_Service_Access::user_ACL';

$config['is_allowed_to[添加].service_project'][] = 'Technical_Service_Access::project_ACL';
$config['is_allowed_to[修改].service_project'][] = 'Technical_Service_Access::project_ACL';
$config['is_allowed_to[删除].service_project'][] = 'Technical_Service_Access::project_ACL';

$config['is_allowed_to[查看].service'][] = 'Technical_Service_Access::service_ACL';
$config['is_allowed_to[导出].service'][] = 'Technical_Service_Access::service_ACL';
$config['is_allowed_to[添加].service'][] = 'Technical_Service_Access::service_ACL';
$config['is_allowed_to[修改].service'][] = 'Technical_Service_Access::service_ACL';
$config['is_allowed_to[修改负责人].service'][] = 'Technical_Service_Access::service_ACL';
$config['is_allowed_to[删除].service'][] = 'Technical_Service_Access::service_ACL';
$config['is_allowed_to[预约服务].service'][] = 'Technical_Service_Access::service_ACL';

$config['is_allowed_to[查看].service_apply'][] = 'Technical_Service_Access::apply_ACL';
$config['is_allowed_to[修改].service_apply'][] = 'Technical_Service_Access::apply_ACL';
$config['is_allowed_to[删除].service_apply'][] = 'Technical_Service_Access::apply_ACL';
$config['is_allowed_to[列表审批].service_apply'][] = 'Technical_Service_Access::apply_ACL';
$config['is_allowed_to[审批].service_apply'][] = 'Technical_Service_Access::apply_ACL';
$config['is_allowed_to[下载结果].service_apply'][] = 'Technical_Service_Access::apply_ACL';

$config['is_allowed_to[列表文件].service_apply'][] = 'Technical_Service_Access::apply_attachments_ACL';
$config['is_allowed_to[下载文件].service_apply'][] = 'Technical_Service_Access::apply_attachments_ACL';
$config['is_allowed_to[上传文件].service_apply'][] = 'Technical_Service_Access::apply_attachments_ACL';
$config['is_allowed_to[修改文件].service_apply'][] = 'Technical_Service_Access::apply_attachments_ACL';
$config['is_allowed_to[删除文件].service_apply'][] = 'Technical_Service_Access::apply_attachments_ACL';

$config['is_allowed_to[结束检测任务].service_apply_record'][] = 'Technical_Service_Access::apply_record_ACL';
$config['is_allowed_to[修改检测结果].service_apply_record'][] = 'Technical_Service_Access::apply_record_ACL';
$config['is_allowed_to[查看结果].service_apply_record'][] = 'Technical_Service_Access::apply_record_ACL';

$config['is_allowed_to[列表文件].service_apply_record'][] = 'Technical_Service_Access::apply_record_attachments_ACL';
$config['is_allowed_to[下载文件].service_apply_record'][] = 'Technical_Service_Access::apply_record_attachments_ACL';
$config['is_allowed_to[上传文件].service_apply_record'][] = 'Technical_Service_Access::apply_record_attachments_ACL';
$config['is_allowed_to[修改文件].service_apply_record'][] = 'Technical_Service_Access::apply_record_attachments_ACL';
$config['is_allowed_to[删除文件].service_apply_record'][] = 'Technical_Service_Access::apply_record_attachments_ACL';

$config['is_allowed_to[修改].eq_sample'][] = ['weight' => -100, 'callback' => 'Technical_Service_Access::eq_sample_ACL'];
$config['is_allowed_to[删除].eq_sample'][] = ['weight' => -100, 'callback' => 'Technical_Service_Access::eq_sample_ACL'];

//计算时长
//$config['service_apply_record_eq_sample.connect'][] = 'Technical_Service::on_relationship_connect';
//$config['service_apply_record_eq_sample.disconnect'][] = 'Technical_Service::on_relationship_disconnect';
//$config['service_apply_record_eq_record.connect'][] = 'Technical_Service::on_relationship_connect';
//$config['service_apply_record_eq_record.disconnect'][] = 'Technical_Service::on_relationship_disconnect';