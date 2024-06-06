<?php

$config['controller[admin/index].ready'][] = 'Achievements_Admin::setup';

/*
NO.TASK#274(guoping.zhang@2010.11.26)
绑定权限判断
*/
$config['is_allowed_to[查看成果].lab'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[添加成果].lab'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[列表成果].lab'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[导入成果].lab'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[修改].publication'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[删除].publication'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[查看].publication'][] = 'Achievements_Access::operate_achievements_is_allowed';

$config['is_allowed_to[修改].patent'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[删除].patent'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[查看].patent'][] = 'Achievements_Access::operate_achievements_is_allowed';

$config['is_allowed_to[修改].award'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[删除].award'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[查看].award'][] = 'Achievements_Access::operate_achievements_is_allowed';

$config['controller[!people/profile].ready'][] = 'Publication::setup_profile';
$config['controller[!people/profile].ready'][] = 'Award::setup_profile';
$config['controller[!people/profile].ready'][] = 'Patent::setup_profile';

$config['is_allowed_to[列表文件].publication'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[下载文件].publication'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[上传文件].publication'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[修改文件].publication'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[删除文件].publication'][] = 'Achievements_Access::operate_achievements_is_allowed';

$config['is_allowed_to[列表文件].patent'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[下载文件].patent'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[上传文件].patent'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[修改文件].patent'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[删除文件].patent'][] = 'Achievements_Access::operate_achievements_is_allowed';

$config['is_allowed_to[列表文件].award'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[下载文件].award'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[上传文件].award'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[修改文件].award'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['is_allowed_to[删除文件].award'][] = 'Achievements_Access::operate_achievements_is_allowed';

$config['publication_model.saved'][] = 'Achievements::on_publication_saved';
$config['patent_model.saved'][] = 'Achievements::on_patent_saved';
$config['award_model.saved'][] = 'Achievements::on_award_saved';

$config['publication_model.before_delete'][] = 'Achievements::before_achievement_delete';
$config['patent_model.before_delete'][] = 'Achievements::before_achievement_delete';
$config['award_model.before_delete'][] = 'Achievements::before_achievement_delete';

$config['user_model.perms.enumerates'][] = 'Achievements::on_enumerate_user_perms';

$config['achievements.author.count'][] = 'Achievements::achievements_author_count';

$config['publication_model.before_save'][] = 'Achievements::update_abbr';
$config['award_model.before_save'][] = 'Achievements::update_abbr';
$config['patent_model.before_save'][] = 'Achievements::update_abbr';
$config['ac_author_model.before_save'][] = 'Achievements::update_abbr';
$config['is_allowed_to[查看成果实验室].achievements'][] = 'Achievements_Access::operate_achievements_is_allowed';
$config['module[achievements].is_accessible'][] = 'Achievements_Access::is_accessible';
