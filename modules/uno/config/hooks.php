<?php
$config['rewrite_base_layout'][] = ['weight' => -999, 'callback' => 'Uno::rewrite_base_layout'];
$config['login.view'] = ['weight' => -999, 'callback' => 'Uno::login_view'];
$config['labs.get_remote_user'] = "Uno::get_remote_user";

# 自动注册信息
$config['system.ready'][] = ['weight' => 999, 'callback' => 'Uno::check_user_stat'];

#自动关联角色
$config['user_model.extra_roles'][] = 'Uno::extra_roles';
$config['user_model.perms.enumerates'][] = 'Uno::on_enumerate_user_perms';

#uno login
$config['api.v1.uno.POST'][] = 'Uno_login::login_post';

// gpui仪器平板,这里用拿着卡号去authx问一圈..
$config['get_user_from_sec_card'][] = 'AuthX_Card::get_user_from_sec_card';
$config['user_model.call.get_user_card'][] = 'AuthX_Card::get_user_card';


$config['admin.index.tab'][] = ['weight' => 999, 'callback' => 'Uno::admin_setup'];