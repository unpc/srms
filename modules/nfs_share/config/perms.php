<?php

if ($GLOBALS['preload']['roles.enable_subgroup_access']) {
    $config['nfs_share']['管理文件分区'] = FALSE;
}

$config['nfs_share']['管理下属组织机构成员的分区'] = FALSE;

$config['nfs_share']['管理本实验室成员的文件分区'] = FALSE;

if ($GLOBALS['preload']['people.multi_lab']) {
    $config['nfs_share']['管理负责实验室成员的文件分区'] = FALSE;
}

$config['nfs_share']['清理文件系统'] = FALSE;

$config['nfs_share']['#name'] = '文件系统';
$config['nfs_share']['#icon'] = '!nfs/icons/32/nfs.png';


// 文件系统
$config['default_roles']['课题组负责人']['default_perms'][] = "管理本实验室成员的文件分区";


if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['nfs_share'] = [];
    $config['nfs_share']['#name'] = '文件系统';
    $config['nfs_share']['#perm_in_uno'] = TRUE;

    $config['nfs_share']['-管理'] = FALSE;

    $config['nfs_share']['管理文件分区'] = FALSE;
    $config['nfs_share']['清理文件系统'] = FALSE;
}
