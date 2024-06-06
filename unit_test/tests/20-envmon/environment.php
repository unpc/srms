<?php
/*
 * @file environment.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 环境监控模块测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/20-envmon/environment
 */

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:envmon模块\n\n";

Environment::init_site();


$role1 = Environment::add_role('环境监控观察员', ['查看环境监控模块']);
$role2 = Environment::add_role('监控对象管理员', ['查看环境监控模块', '管理所有环境监控对象']);
$role3 = Environment::add_role('监控对象负责人', ['查看环境监控模块', '管理负责的监控对象', '管理负责的传感器']);
$role4 = Environment::add_role('传感器管理员', ['查看环境监控模块', '管理所有传感器']);
$role5 = Environment::add_role('监控模块超级管理员', ['查看环境监控模块', '管理所有传感器', '管理所有环境监控对象']);

$user1 = Environment::add_user('刘成');
$user2 = Environment::add_user('吴凯');
$user3 = Environment::add_user('吴天放');
$user4 = Environment::add_user('陈清扬');
$user5 = Environment::add_user('周舟');
$user6 = Environment::add_user('乔巧');
$user7 = Environment::add_user('杨阳');

//设定刘成为监控对象管理员
Environment::set_role($user1,$role2);

//设定吴凯为监控对象负责人
Environment::set_role($user2, $role3);

//设定周舟为监控对象负责人
Environment::set_role($user5, $role3);

//设定吴天放为传感器管理员
Environment::set_role($user3, $role4);

//设定乔巧为环境监控观察员
Environment::set_role($user6, $role1);

//设定杨阳为监控模块超级管理员
Environment::set_role($user7, $role5);

//以下为node增加
$node1 = O('env_node');
$node1->name = '1号冰箱';
$node1->location = '1号教学楼';
$admin_user = O('user', ['name'=>'技术支持']);
$node1->save();
$node1->connect($admin_user);

$node2 = O('env_node');
$node2->name = '2号监控对象';
$node2->location = '3号办公楼';
$node2->save();
$node2->connect($admin_user);

echo "\n环境生成完毕\n";
