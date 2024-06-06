<?php

$config['iot_gdoor']['管理所有仪器的门牌'] = FALSE;
$config['iot_gdoor']['管理负责仪器的门牌'] = FALSE;

$config['iot_gdoor']['查看门牌模块'] = FALSE;

$config['iot_gdoor']['#name'] = '门牌管理';
$config['iot_gdoor']['#icon'] = '!equipments/icons/32/equipments.png';

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['iot_gdoor'] = [];
    $config['iot_gdoor']['#name'] = '门牌管理';
    $config['iot_gdoor']['#perm_in_uno'] = TRUE;

    $config['iot_gdoor']['-管理'] = FALSE;
    $config['iot_gdoor']['查看门牌'] = FALSE;
    $config['iot_gdoor']['-负责'] = FALSE;
    $config['iot_gdoor']['查看负责仪器的门牌'] = FALSE;
}
