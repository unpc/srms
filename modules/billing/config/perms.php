<?php
// (xiaopei.li@2011.03.22)
$config['billing']['查看财务中心'] = FALSE;
$config['billing']['管理财务中心'] = FALSE;

//guoping.zhang@2011.01.14
#ifdef (roles.enable_subgroup_access)
if ($GLOBALS['preload']['roles.enable_subgroup_access']) {
	$config['billing']['列表下属实验室的财务帐号'] = FALSE;
	$config['billing']['修改下属实验室的财务帐号'] = FALSE;
	$config['billing']['列表下属实验室的收支明细'] = FALSE;
}
#endif

if ($GLOBALS['preload']['people.multi_lab']) {
	$config['billing']['列表负责实验室的财务帐号'] = FALSE;
}

$config['billing']['列表本实验室的财务帐号'] = FALSE;

if ($GLOBALS['preload']['people.multi_lab']) {
	$config['billing']['列表负责实验室的收支明细'] = FALSE;
}

$config['billing']['列表本实验室的收支明细'] = FALSE;

$config['billing']['#name'] = '财务管理';
$config['billing']['#icon'] = '!billing/icons/32/billing.png';

$config['default_roles']['课题组负责人']['default_perms'][] = "列表本实验室的财务帐号";
$config['default_roles']['课题组负责人']['default_perms'][] = "列表本实验室的收支明细";

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['billing'] = [];
	$config['billing']['#name'] = '财务';
	$config['billing']['#perm_in_uno'] = TRUE;

	$config['billing']['-管理'] = FALSE;

	$config['billing']['管理财务中心'] = FALSE;
	$config['billing']['列表财务帐号'] = FALSE;
	$config['billing']['修改财务帐号'] = FALSE;
	$config['billing']['列表收支明细'] = FALSE;

	$config['billing']['列表本实验室财务帐号'] = FALSE;
	$config['billing']['列表本实验室收支明细'] = FALSE;
}
