<?php

$config['controller[admin/index].ready'][] = 'Labs_Admin::setup';
$config['controller[admin/index].ready'][] = 'Labs_Support::setup';
$config['controller[!people/profile].ready'][] = 'Labs::setup';

$config['achievements.publication.edit'][] = 'Labs::on_achievement_edit';
$config['achievements.patent.edit'][] = 'Labs::on_achievement_edit';
$config['achievements.award.edit'][] = 'Labs::on_achievement_edit';

$config['publication_model.before_delete'][] = 'Labs::before_lab_relation_delete';
$config['patent_model.before_delete'][] = 'Labs::before_lab_relation_delete';
$config['award_model.before_delete'][] = 'Labs::before_lab_relation_delete';

$config['achievements.publication.save_access'][] = 'Labs::on_lab_project_saved';
$config['achievements.patent.save_access'][] = 'Labs::on_lab_project_saved';
$config['achievements.award.save_access'][] = 'Labs::on_lab_project_saved';

$config['user_model.perms.enumerates'][] = 'Labs::on_enumerate_user_perms';

$config['is_allowed_to[添加].user'][] = 'Labs::user_ACL';
$config['is_allowed_to[查看].user'][] = 'Labs::user_ACL';
$config['is_allowed_to[修改].user'][] = 'Labs::user_ACL';
$config['is_allowed_to[删除].user'][] = 'Labs::user_ACL';
$config['is_allowed_to[管理角色].user'][] = 'Labs::user_ACL';
$config['is_allowed_to[修改实验室].user'][] = 'Labs::user_ACL';
$config['is_allowed_to[修改组织机构].user'][] = 'Labs::user_ACL';
$config['is_allowed_to[查看联系方式].user'][]  = 'Labs::user_ACL';

/*
NO.TASK#274（guoping.zhang@2010.11.25)
操作实验室信息权限绑定
*/
$config['is_allowed_to[查看].lab'][] = 'Labs::operate_lab_is_allowed';
$config['is_allowed_to[添加].lab'][] = 'Labs::operate_lab_is_allowed';
$config['is_allowed_to[导出].lab'][] = 'Labs::operate_lab_is_allowed';//配置导出、打印
$config['is_allowed_to[修改].lab'][] = 'Labs::operate_lab_is_allowed';
$config['is_allowed_to[删除].lab'][] = 'Labs::operate_lab_is_allowed';
$config['is_allowed_to[管理].lab'][] = 'Labs::operate_lab_is_allowed';
$config['is_allowed_to[激活].lab'][] = 'Labs::operate_lab_is_allowed';
$config['is_allowed_to[查看经费].lab'][] = 'Labs::operate_lab_is_allowed';
$config['is_allowed_to[修改组织机构].lab'][] = 'Labs::operate_lab_is_allowed';
$config['is_allowed_to[修改实验室负责人].lab'][] = 'Labs::operate_lab_is_allowed';

$config['is_allowed_to[查看建立者].lab'][] = 'Labs::operate_lab_is_allowed';
$config['is_allowed_to[查看审批者].lab'][] = 'Labs::operate_lab_is_allowed';

$config['is_allowed_to[上传文件].user'][] = 'Labs::operate_attachment_is_allowed';
$config['is_allowed_to[删除文件].user'][] = 'Labs::operate_attachment_is_allowed';
$config['is_allowed_to[下载文件].user'][] = 'Labs::operate_attachment_is_allowed';
$config['is_allowed_to[修改文件].user'][] = 'Labs::operate_attachment_is_allowed';
$config['is_allowed_to[列表文件].user'][] = 'Labs::operate_attachment_is_allowed';

$config['is_allowed_to[添加目录].user'][] = 'Labs::operate_attachment_is_allowed';
$config['is_allowed_to[修改目录].user'][] = 'Labs::operate_attachment_is_allowed';
$config['is_allowed_to[删除目录].user'][] = 'Labs::operate_attachment_is_allowed';

/**
 * @ zhen.liu modified
 */
$config['is_allowed_to[添加成员].lab'][] = 'Labs::operate_lab_user_is_allowed';
$config['is_allowed_to[删除成员].lab'][] = 'Labs::operate_lab_user_is_allowed';
$config['people_users_table.prerender'][] = 'Labs::prerender_people_users_table';

$config['people.index.search.submit'][] = 'Labs::people_lab_selector';

$config['controller[*].ready'][] = 'Labs::accessible_controller';

// (xiaopei.li@2011.08.31)
$config['user_model.before_save'][] = 'Labs::before_user_save';
$config['lab_model.before_save'][] = 'Labs::before_lab_save';
$config['lab_model.saved'][] = 'Labs::on_lab_saved';
//用户注册后对PI的提醒
$config['user_model.saved'][] = 'Labs::register_remind_PI';
$config['lab.notifications.edit'][] = 'Labs::people_register_notifications';
$config['lab.notifications.content'][] = 'Labs::people_register_content';

// 通过登录账号向课题组添加人员
$config['lab.add_member.exsit_token'][] = 'Multi_Labs::add_member_exsit_token';

$config['eq_stat.get_stat_options'][] = 'Labs::get_stat_options';
$config['eq_stat.get_stat_export_options'][] = 'Labs::get_stat_export_options';

$config['lab_model.call.get_project_items'][] = 'Labs::get_project_items';

// 个人门户对接hook
$config['application.component.views'][] = 'Labs_Com::views';
$config['application.component.view.projectStatus'][] = 'Labs_Com::view_projectStatus';
// 不同关联项目不可合并预约
$config['eq_reserv.merge_reserv.extra'][] = 'Lab_Project::eq_reserv_merge_reserv_extra';

$config['user_lab.connect'][] = 'Labs::on_user_connect_lab';


$config['signup.validate_requires'][] = 'labs_support::signup_validate_requires';
