<?php

$config['capability']['#name'] = '仪器绩效考核';
//$config['capability']['#icon'] = '!capability/icons/32/capability.png';

$config['capability']['管理设置考核工作'] = FALSE;
$config['capability']['填报效益'] = FALSE;
$config['capability']['初审绩效'] = FALSE;
$config['capability']['复审绩效'] = FALSE;
$config['capability']['管理下属机构仪器绩效考核'] = FALSE;
$config['capability']['管理所有仪器绩效考核'] = FALSE;
$config['capability']['管理负责仪器绩效考核'] = FALSE;
$config['capability']['录入考核结果'] = FALSE;

$config['default_roles']['仪器负责人']['default_perms'][] = '管理负责仪器绩效考核';
$config['default_roles']['仪器负责人']['default_perms'][] = '填报效益';

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['capability'] = [];
	$config['capability']['#name'] = '绩效考核';
	$config['capability']['#perm_in_uno'] = TRUE;

	$config['capability']['-管理'] = FALSE;
	$config['capability']['管理设置考核工作'] = FALSE;
    $config['capability']['管理仪器绩效考核'] = FALSE;
	$config['capability']['初审绩效'] = FALSE;
	$config['capability']['复审绩效'] = FALSE;
    $config['capability']['录入考核结果'] = FALSE;

    $config['capability']['-负责'] = FALSE;
    $config['capability']['管理负责仪器绩效考核'] = FALSE;
    $config['capability']['填报效益'] = FALSE;
}
