<?php
$config['eq_sample']['-管理 (所有)'] = FALSE;
$config['eq_sample']['修改所有仪器的送样设置'] = FALSE;
$config['eq_sample']['修改所有仪器的送样'] = FALSE;
$config['eq_sample']['-管理 (机构)'] = FALSE;
$config['eq_sample']['修改下属机构仪器的送样设置'] = FALSE;
$config['eq_sample']['修改下属机构仪器的送样'] = FALSE;
$config['eq_sample']['-管理 (负责)'] = FALSE;
$config['eq_sample']['修改负责仪器的送样设置'] = FALSE;
$config['eq_sample']['修改负责仪器的送样'] = FALSE;

$config['eq_sample']['-管理 (实验室)'] = FALSE;
$config['eq_sample']['查看所有实验室的成员送样记录'] = FALSE;
$config['eq_sample']['查看本实验室成员送样记录'] = FALSE;
$config['eq_sample']['下载实验室成员仪器使用送样记录附件'] = FALSE;
if ($GLOBALS['preload']['people.multi_lab']) {
    $config['eq_sample']['查看负责实验室成员送样记录'] = FALSE;
}

$config['eq_sample']['#name'] = '送样预约';
$config['eq_sample']['#icon'] = '!eq_sample/icons/32/eq_sample.png';

$config['default_roles']['课题组负责人']['default_perms'][] = "查看本实验室成员送样记录";
if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['eq_sample'] = [];
    $config['eq_sample']['#name'] = '仪器送样';
    $config['eq_sample']['#perm_in_uno'] = TRUE;

    $config['eq_sample']['-管理'] = FALSE;
    $config['eq_sample']['修改仪器的送样设置'] = FALSE;
    $config['eq_sample']['修改仪器的送样'] = FALSE;
//    $config['eq_sample']['查看仪器的送样记录'] = FALSE;
    $config['eq_sample']['下载实验室成员仪器使用送样记录附件'] = FALSE;
//    $config['eq_sample']['下载仪器使用送样记录附件'] = FALSE;
    $config['eq_sample']['查看实验室的成员送样记录'] = FALSE;


    $config['eq_sample']['-负责'] = FALSE;
    $config['eq_sample']['修改负责仪器的送样设置'] = FALSE;
    $config['eq_sample']['修改负责仪器的送样'] = FALSE;
//    $config['eq_sample']['查看负责仪器的送样记录'] = FALSE;
//    $config['eq_sample']['下载负责仪器使用送样记录附件'] = FALSE;
    $config['eq_sample']['查看本实验室成员送样记录'] = FALSE;
}
