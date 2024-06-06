<?php
/*
 * 已合并至信用分权限
$config['eq_ban']['管理黑名单'] = FALSE;
$config['eq_ban']['管理下属机构的黑名单'] = FALSE;
$config['eq_ban']['管理负责仪器的黑名单'] = FALSE;

$config['eq_ban']['#name'] = '黑名单';
$config['eq_ban']['#icon'] = '!equipments/icons/32/equipments.png';
*/
$config['eq_ban']['管理黑名单'] = FALSE;
$config['eq_ban']['管理下属机构的黑名单'] = FALSE;
$config['eq_ban']['管理负责仪器的黑名单'] = FALSE;

$config['eq_ban']['#name'] = '黑名单';
$config['eq_ban']['#icon'] = '!equipments/icons/32/equipments.png';


if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['eq_ban'] = [];
    $config['eq_ban']['#name'] = '黑名单';
    $config['eq_ban']['#perm_in_uno'] = TRUE;

    $config['eq_ban']['-管理'] = FALSE;
    $config['eq_ban']['管理黑名单'] = FALSE;
}
