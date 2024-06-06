<?php

$config['meeting']['#name'] = '会议室';
$config['meeting']['#icon'] = '!meeting/icons/32/meeting.png';

$config['meeting']['-管理 (所有)'] = FALSE;
$config['meeting']['添加/修改所有会议室'] = FALSE;
$config['meeting']['管理所有会议室的授权'] = FALSE;
$config['meeting']['管理所有会议室的预约'] = FALSE;
$config['meeting']['管理所有会议室的重复预约'] = FALSE;

$config['meeting']['-管理 (负责)'] = FALSE;

$config['meeting']['修改负责会议室信息'] = FALSE;
$config['meeting']['管理负责会议室的授权'] = FALSE;
$config['meeting']['管理负责会议室的预约'] = FALSE;
$config['meeting']['管理负责会议室的重复预约'] = FALSE;


if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['meeting'] = [];
    $config['meeting']['#name'] = '会议室';
    $config['meeting']['#perm_in_uno'] = TRUE;

    $config['meeting']['-管理'] = FALSE;
    $config['meeting']['添加/修改会议室'] = FALSE;
    $config['meeting']['管理会议室的授权'] = FALSE;
    $config['meeting']['管理会议室的预约'] = FALSE;
    $config['meeting']['管理所有会议室的重复预约'] = FALSE;

    $config['meeting']['-负责'] = FALSE;
    $config['meeting']['修改负责会议室信息'] = FALSE;
    $config['meeting']['管理负责会议室的授权'] = FALSE;
    $config['meeting']['管理负责会议室的预约'] = FALSE;
    $config['meeting']['管理负责会议室的重复预约'] = FALSE;
}
