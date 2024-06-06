<?php
$config['announces']['发布公告'] = FALSE;
$config['announces']['审核公告'] = FALSE;

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
    $config['announces'] = [];
    $config['announces']['#name'] = '系统公告';
    $config['announces']['#perm_in_uno'] = TRUE;

    $config['announces']['-管理'] = FALSE;
    $config['announces']['发布公告'] = FALSE;
    $config['announces']['审核公告'] = FALSE;
}
