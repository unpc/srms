<?php
$config['roles']['管理分组'] = FALSE;

$config['roles']['#name'] = '权限管理';
$config['roles']['#icon'] = '!roles/icons/32/roles.png';


$config['perms_not_edit'][] = '课题组负责人';

if ($GLOBALS['preload']['gateway.perm_in_uno']) {
	$config['roles'] = [];
}
