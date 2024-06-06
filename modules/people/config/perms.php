<?php
$config['people']['-管理 (所有)'] = FALSE;
$config['people']['查看成员列表'] = FALSE;
$config['people']['添加/修改所有成员信息'] = FALSE;
$config['people']['修改所有成员的角色'] = FALSE;
$config['people']['上传/创建所有成员的附件'] = FALSE;
$config['people']['更改/删除所有成员的附件'] = FALSE;
$config['people']['查看所有成员的附件'] = FALSE;
$config['people']['下载所有成员的附件'] = FALSE;
$config['people']['查看所有成员的联系方式'] = FALSE;
$config['people']['查看所有成员的登录账号'] = FALSE;

//guoping.zhang@2011.01.14
#ifdef(roles.enable_subgroup_access)
if ($GLOBALS['preload']['roles.enable_subgroup_access']) {
	$config['people']['-管理 (下属)'] = FALSE;
	$config['people']['添加/修改下属机构成员的信息'] = FALSE;
	$config['people']['修改下属机构成员的角色'] = FALSE;
}
#endif

//======= RQ170101 根据权限看到不同范围的成员
if ($GLOBALS['preload']['roles.manage_subgroup_perm']) {
	$config['people']['-管理 (下属)'] = FALSE;
	$config['people']['查看下属机构成员信息'] = FALSE;
}

$config['people']['-其他'] = FALSE;

$config['people']['查看用户建立者'] = FALSE;
$config['people']['查看用户审批者'] = FALSE;
$config['people']['查看其他用户关注的成员'] = FALSE;

$config['people']['#name'] = '成员管理';
$config['people']['#icon'] = '!people/icons/32/people.png';


// $config['default_roles']['课题组负责人']['default_perms'][] = "管理负责实验室订单";
// $config['default_roles']['课题组负责人']['default_perms'][] = "管理本实验室存货";

$config['default_roles']['仪器负责人']['default_perms'][] = '';

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['people'] = [];
	$config['people']['#name'] = '成员';
	$config['people']['#perm_in_uno'] = TRUE;

	$config['people']['-管理'] = FALSE;
//	$config['people']['查看成员信息'] = FALSE;
	$config['people']['修改成员信息'] = FALSE;
	// $config['people']['修改成员角色'] = FALSE;

	// 暂不涉及人员附件的权限
	// $config['people']['上传/创建成员的附件'] = FALSE;
	// $config['people']['更改/删除成员的附件'] = FALSE;
	// $config['people']['查看成员的附件'] = FALSE;
	// $config['people']['下载成员的附件'] = FALSE;
	 $config['people']['查看成员的登录账号'] = FALSE;

	$config['people']['-其他'] = FALSE;

//	$config['people']['查看用户建立者'] = FALSE;
//	$config['people']['查看用户审批者'] = FALSE;
	$config['people']['查看其他用户关注的成员'] = FALSE;
	$config['people']['查看成员的联系方式'] = FALSE;
}
