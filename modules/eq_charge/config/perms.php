<?php
$config['eq_charge']['修改所有仪器的计费设置'] = FALSE;
$config['eq_charge']['查看所有仪器的使用收费情况'] = FALSE;

$config['eq_charge']['修改下属机构仪器的计费设置'] = FALSE;
$config['eq_charge']['查看下属机构仪器的使用收费情况'] = FALSE;

$config['eq_charge']['修改负责仪器的计费设置'] = FALSE;
$config['eq_charge']['查看本实验室的仪器使用收费情况'] = FALSE;

if ($GLOBALS['preload']['people.multi_lab']) {
    $config['eq_charge']['查看负责实验室的仪器使用收费情况'] = FALSE;
}

$config['eq_charge']['查看估计收费情况'] = FALSE;


$config['eq_charge']['确认负责仪器的收费'] = FALSE;

$config['eq_charge']['#name'] = '仪器收费';
$config['eq_charge']['#icon'] = '!eq_charge/icons/32/eq_charge.png';

$config['default_roles']['课题组负责人']['default_perms'][] = "查看本实验室的仪器使用收费情况";

$config['default_roles']['仪器负责人']['default_perms'][] = '确认负责仪器的收费';

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['eq_charge'] = [];
    $config['eq_charge']['#name'] = '仪器收费';
    $config['eq_charge']['#perm_in_uno'] = TRUE;

    $config['eq_charge']['-管理'] = FALSE;

    $config['eq_charge']['修改仪器的计费设置'] = FALSE;
    $config['eq_charge']['查看仪器的收费情况'] = FALSE;
    $config['eq_charge']['查看估计收费情况'] = FALSE;
    $config['eq_charge']['确认仪器的收费'] = FALSE;

    $config['eq_charge']['-负责'] = FALSE;

    $config['eq_charge']['修改负责仪器的计费设置'] = FALSE;
//    $config['eq_charge']['查看负责实验室的仪器使用收费情况'] = FALSE;
    $config['eq_charge']['查看本实验室的仪器使用收费情况'] = FALSE;
    $config['eq_charge']['确认负责仪器的收费'] = FALSE;

}
