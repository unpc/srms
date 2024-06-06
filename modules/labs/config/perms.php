<?php
$config['labs']['-管理 (所有)'] = FALSE;

$config['labs']['查看所有实验室'] = FALSE;
$config['labs']['添加/修改实验室'] = FALSE;
$config['labs']['添加/移除所有实验室的成员'] = FALSE;

$config['labs']['查看所有经费信息'] = FALSE;
$config['labs']['查看实验室建立者'] = FALSE;
$config['labs']['查看实验室审批者'] = FALSE;

//=====================================
if ($GLOBALS['preload']['roles.enable_subgroup_access']) {
$config['labs']['-管理 (机构)'] = FALSE;
//guoping.zhang@2011.01.14
#ifdef(roles.enable_subgroup_access)
	$config['labs']['添加/修改下属机构实验室'] = FALSE;
	$config['labs']['添加/移除下属机构实验室的成员'] = FALSE;
}
#endif

//======== RQ170101 根据权限看到不同范围的成员
if ($GLOBALS['preload']['roles.manage_subgroup_perm']) {
	$config['labs']['查看下属机构课题组'] = FALSE;
}

//=======================================

$config['labs']['-管理 (本实验室)'] = FALSE;

$config['labs']['添加/移除本实验室成员'] = FALSE;
$config['labs']['修改本实验室信息'] = FALSE;
$config['labs']['修改本实验室成员的信息'] = FALSE;
$config['labs']['修改本实验室成员的角色'] = FALSE;
$config['labs']['查看本实验室的经费信息'] = FALSE;

//=======================================
if ($GLOBALS['preload']['people.multi_lab']) {
	$config['labs']['-管理 (负责实验室)'] = FALSE;

	$config['labs']['添加/移除负责实验室成员'] = FALSE;
	$config['labs']['修改负责实验室信息'] = FALSE;
	$config['labs']['修改负责实验室成员的信息'] = FALSE;
	$config['labs']['修改负责实验室成员的角色'] = FALSE;
	$config['labs']['查看负责实验室的经费信息'] = FALSE;
}

//=====================================
$config['labs']['#name'] = '实验室管理';
$config['labs']['#icon'] = '!labs/icons/32/labs.png';

// 课题组
$config['default_roles']['课题组负责人']['default_perms'][] = "查看本实验室的经费信息";
$config['default_roles']['课题组负责人']['default_perms'][] = "修改本实验室成员的信息";
$config['default_roles']['课题组负责人']['default_perms'][] = "修改本实验室信息";
$config['default_roles']['课题组负责人']['default_perms'][] = "添加/移除本实验室成员";


if ($GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['labs'] = [];
    $config['labs']['#name'] = '实验室';
    $config['labs']['#perm_in_uno'] = TRUE;

    $config['labs']['-管理'] = FALSE;
    $config['labs']['修改实验室'] = FALSE;
//    $config['labs']['添加/移除实验室的成员'] = FALSE;
    $config['labs']['查看经费信息'] = FALSE;
//    $config['labs']['查看实验室建立者'] = FALSE;
//    $config['labs']['查看实验室审批者'] = FALSE;

    $config['labs']['-负责'] = FALSE;
//    $config['labs']['添加/移除本实验室成员'] = FALSE;
    $config['labs']['修改本实验室信息'] = FALSE;
    $config['labs']['修改本实验室成员的信息'] = FALSE;
//    $config['labs']['修改本实验室成员的角色'] = FALSE;
    $config['labs']['查看本实验室的经费信息'] = FALSE;



}