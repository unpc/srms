<?php
$config['admin'] = ['unpc|database', 'support@booguo.com|database'];

$config['menu_disabled_section'] = '禁用';
$config['currency_sign']         = '¥';
$config['default']               = 'test';
$config['precious_price']        = 400000;
$config['help.email']            = 'support@booguo.com';

$config['config_engine'] = 'MyISAM'; //系统配置 修改较少 读取很多 采用MyISAM

//更新首页模块数组
$config['update_module_name'] = ['user', 'equipment', 'order', 'stock', 'eq_record', 'tn_project'];

//系统提供给用户的自定义首页
$config['home_list'] = [
    'update'    => '个人更新页面',
    'sbmenu'    => '边栏菜单列表',
    'me'        => '个人信息页面',
    'dashboard' => '个人主页',
];

//默认用户首页
$config['default_home'] = 'dashboard';
