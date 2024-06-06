<?php

$config['eq_mon']['实时监控所有仪器'] = FALSE;

if ($GLOBALS['preload']['roles.enable_subgrouop_access']) {
    $config['eq_mon']['实时监控下属机构的仪器'] = FALSE;
}

$config['eq_mon']['实时监控负责的仪器'] = FALSE;

//======================================
$config['eq_mon']['#name'] = '仪器监控';
$config['eq_mon']['#icon'] = '!equipments/icons/32/equipments.png';

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['eq_mon'] = [];
    $config['eq_mon']['#name'] = '仪器监控';
    $config['eq_mon']['#perm_in_uno'] = TRUE;

    $config['eq_mon']['-管理'] = FALSE;
    $config['eq_mon']['实时监控仪器'] = FALSE;

    $config['eq_mon']['-负责'] = FALSE;
    $config['eq_mon']['实时监控负责仪器'] = FALSE;
}
