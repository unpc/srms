<?php
$config['eq_comment']['管理所有仪器的使用评价'] = FALSE;
$config['eq_comment']['管理下属机构仪器的使用评价'] = FALSE;

$config['eq_comment']['#name'] = '双向评价';
$config['eq_comment']['#icon'] = '!eq_comment/icons/32/eq_comment.png';

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['eq_comment'] = [];
    $config['eq_comment']['#name'] = '双向评价';
    $config['eq_comment']['#perm_in_uno'] = TRUE;

    $config['eq_comment']['-管理'] = FALSE;
    $config['eq_comment']['管理仪器的使用评价'] = FALSE;
}
