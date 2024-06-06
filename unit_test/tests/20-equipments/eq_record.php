<?php
/*
 * @file eq_record.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 仪器模块仪器使用记录测试环境脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/20-equipments/eq_record
 */


require_once(ROOT_PATH.'unit_test/helpers/environment.php');
define('DISABLE_NOTIFICATION', TRUE);

echo "开始环境自动生成:Euipments模块\n\n";

Environment::init_site();

$super_user = O('user', ['name'=>'技术支持']);
$group1 = Environment::add_group('天津大学');
$group2 = Environment::add_group('电信学院', $group1);
$group3 = Environment::add_group('计算机学院', $group1);

$eq_tag1 = Environment::add_eq_tag('计算机');
$eq_tag2 = Environment::add_eq_tag('电信');

$user1 = Environment::add_user('马睿');
$user2 = Environment::add_user('吴天放');
$user3 = Environment::add_user('吴凯');
$user4 = Environment::add_user('刘成');

$role1 = Environment::add_role('仪器负责人', ['管理负责仪器的临时用户', '修改负责仪器的使用记录']);
$role2 = Environment::add_role('组织机构管理员', ['查看下属机构的仪器使用记录', '修改下属机构仪器的使用记录', '管理下属机构仪器的临时用户']);
$role3 = Environment::add_role('系统管理员', ['查看所有仪器的使用记录', '修改所有仪器的使用记录', '管理所有仪器的临时用户']);

Environment::set_role($user2, $role1);
Environment::set_role($user3, $role2);
Environment::set_role($user4, $role3);

Environment::set_group($user3, $group2);

$eq1 = Environment::set_group(Environment::add_equipment('深蓝I', $user2, $user2), $group2);
$eq2 = Environment::set_group(Environment::add_equipment('深蓝II', $super_user, $super_user), $group3);

foreach(Q('user') as $u) {
    //仪器使用时间为100，防止出现时间重叠
    Environment::add_eq_record($eq1, $u, 100);
    Environment::add_eq_record($eq2, $u, 100);
}

echo "\n环境生成完毕\n";
