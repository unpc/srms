<?php
$config['projects']['添加/修改项目'] = FALSE;
$config['projects']['查看所有项目'] = FALSE;
$config['projects']['添加/修改任务'] = FALSE;
$config['projects']['查看所有任务'] = FALSE;

$config['projects']['#name'] = '项目管理';
$config['projects']['#icon'] = '!projects/icons/32/projects.png';

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['projects'] = [];
    $config['projects']['#name'] = '实验室项目管理';
    $config['projects']['#perm_in_uno'] = TRUE;

    $config['projects']['-管理'] = FALSE;
    $config['projects']['添加/修改项目'] = FALSE;
    $config['projects']['查看项目'] = FALSE;
    $config['projects']['添加/修改任务'] = FALSE;
    $config['projects']['查看任务'] = FALSE;
}
