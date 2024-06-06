<?php

$config['vidmon']['#name'] = '视频监控';
$config['vidmon']['#icon'] = '!vidmon/icons/32/vidmon.png';

$config['vidmon']['查看视频监控模块'] = FALSE;
$config['vidmon']['管理视频设备'] = FALSE;
$config['vidmon']['监控视频设备'] = FALSE;


if ($GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['vidmon'] = [];
	$config['vidmon']['#name'] = '视频监控';
	$config['vidmon']['#perm_in_uno'] = TRUE;

	$config['vidmon']['-管理'] = FALSE;

	$config['vidmon']['查看视频监控模块'] = FALSE;
	$config['vidmon']['管理视频设备'] = FALSE;
	$config['vidmon']['监控视频设备'] = FALSE;
}
