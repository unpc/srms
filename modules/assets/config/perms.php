<?php

$config['assets']['#name'] = '资产同步管理';

$config['assets']['#icon'] = '!equipments/icons/32/equipments.png';

$config['assets']['管理资产同步'] = FALSE;

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['assets'] = [];
    $config['assets']['#name'] = '资产同步';
    $config['assets']['#perm_in_uno'] = TRUE;

    $config['assets']['-管理'] = FALSE;
    $config['assets']['管理资产同步'] = FALSE;
}
