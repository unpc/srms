<?php
/* TASK#1431
 * 删除“修改仪器的使用记录”，由以下三个权限代替：
 *    1. 修改所有仪器的使用记录
 *    2. 修改负责仪器的使用记录
 *    3. 修改下属机构仪器的使用记录
 * 增加以下两个权限：
 *    1. 管理所有仪器的临时用户
 *    2. 管理负责仪器的临时用户
 * */
$config['equipments']['-管理 (所有)'] = FALSE;

$config['equipments']['添加/修改所有机构的仪器'] = FALSE;
$config['equipments']['修改所有仪器的使用设置'] = FALSE;
$config['equipments']['修改所有仪器的状态设置'] = FALSE;
$config['equipments']['修改所有仪器的用户标签'] = FALSE;
$config['equipments']['报废所有仪器'] = FALSE;

$config['equipments']['查看所有仪器的使用记录'] = FALSE;
$config['equipments']['修改所有仪器的使用记录'] = FALSE;
$config['equipments']['管理所有仪器的培训记录'] = FALSE;
$config['equipments']['管理所有仪器的临时用户'] = FALSE;

$config['equipments']['查看所有仪器的附件'] = FALSE;
$config['equipments']['上传/创建所有仪器的附件'] = FALSE;
$config['equipments']['更改/删除所有仪器的附件'] = FALSE;
$config['equipments']['下载所有仪器的附件'] = FALSE;



//=======================================
if ($GLOBALS['preload']['roles.enable_subgroup_access']) {
    $config['equipments']['-管理 (机构)'] = FALSE;

    $config['equipments']['添加/修改下属机构的仪器'] = FALSE;
    $config['equipments']['修改下属机构仪器的使用设置'] = FALSE;
    $config['equipments']['修改下属机构仪器的状态设置'] = FALSE;
    $config['equipments']['修改下属机构仪器的用户标签'] = FALSE;
    $config['equipments']['报废下属机构的仪器'] = FALSE;

    $config['equipments']['查看下属机构的仪器使用记录'] = FALSE;
    $config['equipments']['修改下属机构仪器的使用记录'] = FALSE;
    $config['equipments']['管理下属机构仪器的临时用户'] = FALSE;
    $config['equipments']['管理下属机构仪器的培训记录'] = FALSE;

    $config['equipments']['上传/创建下属机构仪器的附件'] = FALSE;
    $config['equipments']['更改/删除下属机构仪器的附件'] = FALSE;
    $config['equipments']['下载下属机构仪器的附件'] = FALSE;
}

//======================================
$config['equipments']['-管理 (负责)'] = FALSE;

$config['equipments']['添加负责的仪器'] = FALSE;
$config['equipments']['修改负责的仪器'] = FALSE;

$config['equipments']['修改负责仪器的使用设置'] = FALSE;
$config['equipments']['修改负责仪器的状态设置'] = FALSE;
$config['equipments']['修改负责仪器的用户标签'] = FALSE;

$config['equipments']['管理负责仪器的临时用户'] = FALSE;
$config['equipments']['管理负责仪器的培训记录'] = FALSE;
$config['equipments']['查看负责仪器的使用记录'] = FALSE;
$config['equipments']['修改负责仪器的使用记录'] = FALSE;

$config['equipments']['报废负责仪器'] = FALSE;

$config['equipments']['删除负责仪器'] = FALSE;

$config['equipments']['查看负责仪器的附件'] = FALSE;
$config['equipments']['上传/创建负责仪器的附件'] = FALSE;
$config['equipments']['更改/删除负责仪器的附件'] = FALSE;
$config['equipments']['下载负责仪器的附件'] = FALSE;

//======================================
$config['equipments']['-常规'] = FALSE;

if ($GLOBALS['preload']['equipments.multi_lab']) {
    $config['equipments']['查看负责实验室成员的仪器使用情况'] = FALSE;
}

$config['equipments']['查看本实验室成员的仪器使用情况'] = FALSE;
$config['equipments']['查看其他用户关注的仪器'] = FALSE;

//======================================
$config['equipments']['#name'] = '仪器管理';
$config['equipments']['#icon'] = '!equipments/icons/32/equipments.png';

$config['default_roles']['课题组负责人']['default_perms'][] = "查看本实验室成员的仪器使用情况";

$config['default_roles']['仪器负责人']['default_perms'][] = '修改负责的仪器';
$config['default_roles']['仪器负责人']['default_perms'][] = '修改负责仪器的用户标签';
$config['default_roles']['仪器负责人']['default_perms'][] = '查看负责仪器的使用记录';
$config['default_roles']['仪器负责人']['default_perms'][] = '查看负责仪器的附件';
$config['default_roles']['仪器负责人']['default_perms'][] = '上传/创建负责仪器的附件';
$config['default_roles']['仪器负责人']['default_perms'][] = '更改/删除负责仪器的附件';
$config['default_roles']['仪器负责人']['default_perms'][] = '下载负责仪器的附件';

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['equipments'] = [];
    $config['equipments']['#name'] = '仪器';
    $config['equipments']['#perm_in_uno'] = TRUE;

    $config['equipments']['-管理'] = FALSE;

    $config['equipments']['添加/修改仪器'] = FALSE;
    $config['equipments']['修改仪器的使用设置'] = FALSE;
    $config['equipments']['修改仪器的状态设置'] = FALSE;
    $config['equipments']['修改仪器的用户标签'] = FALSE;
    $config['equipments']['删除仪器'] = FALSE;
    $config['equipments']['报废仪器'] = FALSE;

    $config['equipments']['查看仪器的使用记录'] = FALSE;
    $config['equipments']['修改仪器的使用记录'] = FALSE;
    $config['equipments']['管理仪器的培训记录'] = FALSE;
    $config['equipments']['管理仪器的临时用户'] = FALSE;

    $config['equipments']['查看仪器的附件'] = FALSE;
    $config['equipments']['上传/创建仪器的附件'] = FALSE;
    $config['equipments']['更改/删除仪器的附件'] = FALSE;
    $config['equipments']['下载仪器的附件'] = FALSE;

    $config['equipments']['-常规'] = FALSE;
//    $config['equipments']['查看仪器使用情况'] = FALSE;
    $config['equipments']['查看其他用户关注的仪器'] = FALSE;

    $config['equipments']['-负责'] = false;

    $config['equipments']['添加负责的仪器'] = FALSE;
    $config['equipments']['修改负责的仪器'] = FALSE;

    $config['equipments']['修改负责仪器的使用设置'] = FALSE;
    $config['equipments']['修改负责仪器的状态设置'] = FALSE;
    $config['equipments']['修改负责仪器的用户标签'] = FALSE;

    $config['equipments']['管理负责仪器的临时用户'] = FALSE;
    $config['equipments']['管理负责仪器的培训记录'] = FALSE;
    $config['equipments']['查看负责仪器的使用记录'] = FALSE;
    $config['equipments']['修改负责仪器的使用记录'] = FALSE;
    $config['equipments']['查看本实验室成员的仪器使用情况'] = FALSE;

    $config['equipments']['报废负责仪器'] = FALSE;

    $config['equipments']['删除负责仪器'] = FALSE;

    $config['equipments']['查看负责仪器的附件'] = FALSE;
    $config['equipments']['上传/创建负责仪器的附件'] = FALSE;
    $config['equipments']['更改/删除负责仪器的附件'] = FALSE;
    $config['equipments']['下载负责仪器的附件'] = FALSE;

}
