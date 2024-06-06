<?php

// 黑名单转为内置模块
unset($config['eq_ban']);
$config['credit']['管理所有成员信用分'] = false;
$config['credit']['管理下属机构成员的信用分'] = false;
$config['credit']['管理负责仪器的黑名单'] = false;

$config['credit']['#name'] = '信用分';
$config['credit']['#icon'] = '!equipments/icons/32/equipments.png';


if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['credit'] = [];
    $config['credit']['#name'] = '信用分';
    $config['credit']['#perm_in_uno'] = TRUE;

    $config['credit']['-管理'] = FALSE;
    $config['credit']['管理信用分'] = FALSE;

    $config['credit']['-负责'] = FALSE;
    $config['credit']['管理负责仪器的黑名单'] = false;
}
