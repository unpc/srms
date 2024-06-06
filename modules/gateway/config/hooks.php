<?php

$config['controller[!people/profile].ready'][] = 'Gateway::setup';

$config['is_allowed_to[添加].lab'][] = ['weight' => -999, 'callback' => 'Gateway::operate_lab_is_allowed'];
$config['is_allowed_to[添加].user'][] = ['weight' => -999, 'callback' => 'Gateway::user_ACL'];

$config['user.links'][] = 'Gapper_User::user_links';

$config['gapper_groups.lab.delete'][] = 'Gapper_Update::after_remote_lab_delete';
$config['gapper_user.user.delete'][] = 'Gapper_Update::after_remote_user_delete';

$config['get_user_by_token'][] = 'Gapper_User::get_user';
