<?php

$config['equipments']['管理所有仪器的使用评价'] = FALSE;
$config['equipments']['管理下属机构仪器的使用评价'] = FALSE;

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['eq_evaluate'] = [];
    $config['eq_evaluate']['#name'] = '仪器使用评价';
    $config['eq_evaluate']['#perm_in_uno'] = TRUE;

    $config['eq_evaluate']['-管理'] = FALSE;
    $config['eq_evaluate']['管理仪器的使用评价'] = FALSE;
}
