<?php

$config['system.setup'][] = 'Application::setup';
$config['system.shutdown'][] = 'Application::shutdown';

$config['orm_model.before_save'][] = 'Lab::save_abbr';
$config['auth.logout'][] = 'Lab::forget_login';

$config['is_allowed_to[删除].comment'][] = 'Comment_Model::comment_ACL';

$config['support.online.kf5'][] = 'Support::online_kf5';

// $config['user_model.extra_roles'][] = 'Role::extra_roles';

// $config['markup.view'][] = array('callback'=> 'Comments_Widget::at_user', 'weight'=>-1);

// 全文搜索可配, 详见hvri/modules/eq_sample/config/sphinx.php
$config['orm_model.saved'][] = 'Sphinx_Search::orm_model_saved';
$config['auth.login'][] = ['callback' => 'Application::auth_login', 'weight' => -1];

$config['api.v1.agent-token.POST'][] = 'API_V1::agent_token_post';
$config['api.v1.middlewares.*'][] = 'API_Middlewares::gapperTokenAuth';
$config['api.v1.middlewares.*'][] = 'API_Middlewares::getGapperUserByToken';
$config['equipment.api.v1.middlewares.*'][] = 'API_Middlewares::gapperTokenAuth';
$config['equipment.api.v1.middlewares.*'][] = 'API_Middlewares::getGapperUserByToken';
$config['billing.api.v1.middlewares.*'][] = 'API_Middlewares::gapperTokenAuth';
$config['billing.api.v1.middlewares.*'][] = 'API_Middlewares::getGapperUserByToken';
$config['message.api.v1.middlewares.*'][] = 'API_Middlewares::gapperTokenAuth';
$config['message.api.v1.middlewares.*'][] = 'API_Middlewares::getGapperUserByToken';
$config['user.api.v1.middlewares.*'][] = 'API_Middlewares::gapperTokenAuth';
$config['user.api.v1.middlewares.*'][] = 'API_Middlewares::getGapperUserByToken';
$config['nfs.api.v1.middlewares.*'][] = 'API_Middlewares::gapperTokenAuth';
$config['nfs.api.v1.middlewares.*'][] = 'API_Middlewares::getGapperUserByToken';
$config['api.v1.current-user.GET'][] = 'API_Middlewares::getLimsCurrentUser';

$config['api.v1.group.root.GET'][] = 'Group_API::GroupRoot_get';
$config['api.v1.group.children.GET'][] = 'Group_API::Groups_get';
$config['api.v1.group.GET'][] = 'Group_API::Group_get';