<?php
$config['technical_service']['-管理 (所有)'] = FALSE;

$config['technical_service']['修改所有仪器的服务项目'] = FALSE;
$config['technical_service']['管理所有服务'] = FALSE;

if ($GLOBALS['preload']['roles.enable_subgroup_access']) {
    $config['technical_service']['-管理 (机构)'] = FALSE;

    $config['technical_service']['修改下属机构仪器的服务项目'] = FALSE;
    $config['technical_service']['管理下属机构服务'] = FALSE;

}

$config['technical_service']['-管理 (负责)'] = FALSE;

$config['technical_service']['修改负责仪器的服务项目'] = FALSE;
$config['technical_service']['管理负责服务'] = FALSE;

$config['technical_service']['#name'] = '技术服务管理';
$config['technical_service']['#icon'] = '!technical_service/icons/32/technical_service.png';

$config['default_roles']['仪器负责人']['default_perms'][] = '修改负责仪器的服务项目';

$config['summary']['[大数据体系]查看所有服务记录'] = false;
$config['summary']['[大数据体系]查看下属机构服务记录'] = false;


if ($GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['technical_service'] = [];
	$config['technical_service']['#name'] = '技术服务';
	$config['technical_service']['#perm_in_uno'] = TRUE;

	$config['technical_service']['-管理'] = FALSE;

	$config['technical_service']['管理服务'] = FALSE;
	$config['technical_service']['修改仪器的服务项目'] = FALSE;

    $config['technical_service']['-负责'] = FALSE;
	$config['technical_service']['修改负责仪器的服务项目'] = FALSE;
	$config['technical_service']['管理负责服务'] = FALSE;
}
