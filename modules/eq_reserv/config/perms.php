<?php

//========================
$config['eq_reserv']['-管理 (所有)'] = FALSE;

$config['eq_reserv']['修改所有仪器的预约设置'] = FALSE;
$config['eq_reserv']['为所有仪器添加预约'] = FALSE;
$config['eq_reserv']['为所有仪器添加重复预约事件'] = FALSE;
$config['eq_reserv']['修改所有仪器的预约'] = FALSE;
$config['eq_reserv']['删除所有仪器的预约'] = FALSE;
$config['eq_reserv']['修改用户的预约违规次数'] = FALSE;

//========================
if ($GLOBALS['preload']['roles.enable_subgroup_access']) {
    $config['eq_reserv']['-管理 (机构)'] = FALSE;

    $config['eq_reserv']['修改下属机构仪器的预约设置'] = FALSE;
    $config['eq_reserv']['为下属机构仪器添加预约'] = FALSE;
    $config['eq_reserv']['为下属机构仪器添加重复预约事件'] = FALSE;
    $config['eq_reserv']['修改下属机构仪器的预约'] = FALSE;
    $config['eq_reserv']['删除下属机构仪器的预约'] = FALSE;
}

//========================
$config['eq_reserv']['-管理 (负责)'] = FALSE;

$config['eq_reserv']['修改负责仪器的预约设置'] = FALSE;
$config['eq_reserv']['为负责仪器添加预约'] = FALSE;
$config['eq_reserv']['为负责仪器添加重复预约事件'] = FALSE;
$config['eq_reserv']['修改负责仪器的预约'] = FALSE;
$config['eq_reserv']['删除负责仪器的预约'] = FALSE;
$config['eq_reserv']['审批负责仪器的预约'] = FALSE;

//========================
$config['eq_reserv']['-管理 (实验室)'] = FALSE;

if ($GLOBALS['preload']['people.multi_lab']) {
    $config['eq_reserv']['查看负责实验室成员的预约情况'] = FALSE;
}

$config['eq_reserv']['查看本实验室成员的预约情况'] = FALSE;

//========================
$config['eq_reserv']['#name'] = '仪器预约';
$config['eq_reserv']['#icon'] = '!eq_reserv/icons/32/eq_reserv.png';


$config['default_roles']['课题组负责人']['default_perms'][] = "查看本实验室成员的预约情况";

$config['default_roles']['仪器负责人']['default_perms'][] = '审批负责仪器的预约';
if ($GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['eq_reserv'] = [];
	$config['eq_reserv']['#name'] = '仪器预约';
	$config['eq_reserv']['#perm_in_uno'] = TRUE;

	$config['eq_reserv']['-管理'] = FALSE;

    $config['eq_reserv']['修改仪器的预约设置'] = FALSE;
    $config['eq_reserv']['为仪器添加预约'] = FALSE;
    $config['eq_reserv']['为仪器添加重复预约事件'] = FALSE;
    $config['eq_reserv']['修改仪器的预约'] = FALSE;
    $config['eq_reserv']['删除仪器的预约'] = FALSE;
    $config['eq_reserv']['修改用户的预约违规次数'] = FALSE;
    $config['eq_reserv']['查看仪器的预约情况'] = FALSE;

    $config['eq_reserv']['-负责'] = FALSE;

    $config['eq_reserv']['修改负责仪器的预约设置'] = FALSE;
    $config['eq_reserv']['为负责仪器添加预约'] = FALSE;
    $config['eq_reserv']['为负责仪器添加重复预约事件'] = FALSE;
    $config['eq_reserv']['修改负责仪器的预约'] = FALSE;
    $config['eq_reserv']['删除负责仪器的预约'] = FALSE;
    // $config['eq_reserv']['修改用户的预约违规次数'] = FALSE;
    $config['eq_reserv']['审批负责仪器的预约'] = FALSE;
    $config['eq_reserv']['查看负责仪器的预约情况'] = FALSE;
    $config['eq_reserv']['查看本实验室成员的预约情况'] = FALSE;
}
