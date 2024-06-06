<?php

$config['application']['管理所有内容'] = FALSE;
$config['application']['管理组织机构'] = FALSE;


if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['application'] = [];
    $config['application']['#name'] = '系统管理';
    $config['application']['#perm_in_uno'] = TRUE;

    $config['application']['-管理'] = FALSE;
    $config['application']['管理所有内容'] = FALSE;
    $config['application']['管理组织机构'] = FALSE;
}
