<?php

/*
 * @file environment.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 仪器收费模块测试用例环境架设脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-eq_charge/environment
 */
if (!Module::is_installed('eq_charge') || !Module::is_installed('billing')) return true;
require_once(ROOT_PATH.'unit_test/helpers/environment.php');
echo "开始环境自动生成:eq_charge模块\n\n";

Environment::init_site();

Environment::set_config('equipment.reserv_max_time', 0);
Environment::set_config('equipment.modify_time_limit', 0);

$user1 = Environment::add_user('陈建宁');
$user2 = Environment::add_user('许宏山');
$user3 = Environment::add_user('胡宁');
$user4 = Environment::add_user('程莹');
$user5 = Environment::add_user('柴志华');

$equ1  = Environment::add_equipment('X射线能谱仪', $user2, $user1);

Environment::equ_add_tag($equ1, 'VIP', $user4);
$equ1->accept_reserv = true;
$equ1->save();

$role1 = Environment::add_role('仪器管理员',[
	'查看所有仪器的使用收费情况',
	'查看所有仪器的使用记录',
	'修改所有仪器的计费设置',
	'添加/修改所有机构的仪器',
	'修改所有仪器的使用记录',
	'修改所有仪器的送样设置',
	'修改所有仪器的送样',
]);

$role2 = Environment::add_role('仪器负责人',[
	'修改负责仪器的计费设置',
]);

Environment::set_role($user2, $role2);
Environment::set_role($user3, $role1);

if (!$GLOBALS['preload']['billing.single_department']) {
    //多财务部门模式下相关设定
    Environment::add_department('天津大学财务处');
    $department = O('billing_department', ['name'=>'天津大学财务处']);

    $equ1->billing_dept = $department;
    $equ1->save();
        
}
else {
    //单财务部门模式下相关设定
    $department = BIlling_department::get();
}

//增加财务帐号
Environment::add_account($user1->lab, $department);
