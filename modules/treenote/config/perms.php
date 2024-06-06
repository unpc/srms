<?php

$config['treenote']['管理所有项目'] = FALSE;

if ($GLOBALS['preload']['roles.enable_subgroup_access']) {
    $config['treenote']['管理下属机构成员的任务'] = FALSE;
}

$config['treenote']['#name'] = '项目管理';
$config['treenote']['#icon'] = '!treenote/icons/32/treenote.png';
