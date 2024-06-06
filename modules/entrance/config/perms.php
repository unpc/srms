<?php

$config['entrance']['查看门禁模块'] = FALSE;
$config['entrance']['管理所有门禁'] = FALSE;
$config['entrance']['管理负责的门禁'] = FALSE;
$config['entrance']['远程控制负责的门禁'] = FALSE;
$config['entrance']['查看所有门禁的进出记录'] = FALSE;
$config['entrance']['查看负责课题组的进出记录'] = FALSE;
$config['entrance']['查看负责仪器关联的进出记录'] = FALSE;

$config['entrance']['#name'] = '门禁管理';
$config['entrance']['#icon'] = '!entrance/icons/32/entrance.png';

$config['default_roles']['课题组负责人']['default_perms'][] = '查看负责课题组的进出记录';
$config['default_roles']['仪器负责人']['default_perms'][] = '查看负责仪器关联的进出记录';
$config['default_roles']['仪器负责人']['default_perms'][] = '查看门禁模块';


if ($GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['entrance'] = [];
	$config['entrance']['#name'] = '门禁';
	$config['entrance']['#perm_in_uno'] = TRUE;

	$config['entrance']['-管理'] = FALSE;
    $config['entrance']['查看门禁模块'] = FALSE;
	$config['entrance']['管理所有门禁'] = FALSE;
	$config['entrance']['查看所有门禁的进出记录'] = FALSE;

    $config['entrance']['-负责'] = FALSE;
	$config['entrance']['管理负责的门禁'] = FALSE;
	$config['entrance']['远程控制负责的门禁'] = FALSE;
    $config['entrance']['查看负责实验室的进出记录'] = FALSE;
    $config['entrance']['查看负责仪器关联的进出记录'] = FALSE;
}