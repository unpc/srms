<?php

/*
 * @file equipments.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 * 
 * @brief 仪器模块测试环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/20-equipments/equipments
 */

require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:Euipments模块\n\n";

Environment::init_site();

$super_user = O('user', ['name'=>'技术支持']);
$group1 = Environment::add_group('南开大学');
$group2 = Environment::add_group('电信学院', $group1);
$group3 = Environment::add_group('计算机学院', $group1);

$eq_tag1 = Environment::add_eq_tag('计算机');
$eq_tag2 = Environment::add_eq_tag('电信');

$user1 = Environment::add_user('马睿');
$user2 = Environment::add_user('吴天放');
$user3 = Environment::add_user('吴凯');
$user4 = Environment::add_user('刘成');

$role1 = Environment::add_role('仪器负责人', ['修改负责仪器的使用设置', '修改负责仪器的状态设置', '报废负责仪器']);
$role2 = Environment::add_role('仪器组织机构管理员', ['添加/修改下属机构的仪器', '修改下属机构仪器的使用设置', '修改下属机构仪器的控制方式', '修改下属机构的仪器标签', '将用户加入下属机构的仪器使用黑名单', '修改下属机构仪器的状态设置', '报废下属机构的仪器']);
$role3 = Environment::add_role('仪器系统管理员', ['添加/修改所有机构的仪器', '修改所有仪器的使用设置', '修改所有仪器标签', '将用户加入仪器使用黑名单', '修改所有仪器的状态设置', '报废所有仪器']);

Environment::set_role($user2, $role1);
Environment::set_role($user3, $role2);
Environment::set_role($user4, $role3);

Environment::set_group($user3, $group2);

$eq1 = Environment::set_group(Environment::add_equipment('表面形貌测量仪', $user2, $user2), $group2);
$eq2 = Environment::set_group(Environment::add_equipment('表面形貌测量仪II', $user2, $user2), $group2);

$eq3 = Environment::set_group(Environment::add_equipment('深蓝II', $super_user, $super_user), $group3);
$eq4 = Environment::set_group(Environment::add_equipment('深蓝III', $super_user, $super_user), $group3);

$eq1->ref_no = '001';
$eq1->location = '一号教学楼';
$eq1->location2 = '1006室';
echo T('设定 %name 编号、位置', ['%name'=>$eq1->name]);
if ($eq1->save()) {
    Environment::echo_green("成功\n") ;
}
else {
    Environment::echo_error("失败\n");
}

$eq2->ref_no = '011';
$eq2->location = '一号教学楼';
$eq2->location2 = '1007室';
echo T('设定 %name 编号、位置', ['%name'=>$eq2->name]);
if ($eq2->save()) {
    Environment::echo_green("成功\n") ;
}
else {
    Environment::echo_error("失败\n");
}

$eq3->ref_no = '002';
$eq3->location = '二号教学楼';
$eq3->location2 = '2007室';
echo T('设定 %name 编号、位置', ['%name'=>$eq3->name]);
if ($eq3->save()) {
    Environment::echo_green("成功\n") ;
}
else {
    Environment::echo_error("失败\n");
}

$eq4->ref_no = '012';
$eq4->location = '二号教学楼';
$eq4->location2 = '2008室';
echo T('设定 %name 编号、位置', ['%name'=>$eq4->name]);
if ($eq4->save()) {
    Environment::echo_green("成功\n") ;
}
else {
    Environment::echo_error("失败\n");
}

echo T('设定 %name 分类为%tag', ['%name'=>$eq1->name, '%tag'=>$eq_tag2->name]);
if ($eq1->connect($eq_tag2)) {
    Environment::echo_green("成功\n") ;
}
else {
    Environment::echo_error("失败\n");
}

echo T('设定 %name 分类为%tag', ['%name'=>$eq2->name, '%tag'=>$eq_tag2->name]);
if ($eq2->connect($eq_tag2)) {
    Environment::echo_green("成功\n") ;
}
else {
    Environment::echo_error("失败\n");
}

echo T('设定 %name 分类为%tag', ['%name'=>$eq3->name, '%tag'=>$eq_tag1->name]);
if ($eq3->connect($eq_tag1)) {
    Environment::echo_green("成功\n") ;
}
else {
    Environment::echo_error("失败\n");
}

echo T('设定 %name 分类为%tag', ['%name'=>$eq3->name, '%tag'=>$eq_tag1->name]);
if ($eq4->connect($eq_tag1)) {
    Environment::echo_green("成功\n") ;
}
else {
    Environment::echo_error("失败\n");
}

echo "\n环境生成完毕\n";
