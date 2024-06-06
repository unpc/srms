#!/usr/bin/env php
<?php
require '../base.php';
$users = Q('user');
foreach ($users as $user) {
	echo "升级{$user->name}[$user->id]\n";
	$type = $user->member_type;
	$id = $user->id;
	`mysql -u genee --default-character-set=utf8 -e "update lims2_nankai_sky.user set member_type='$type' where id=$id"`;
	`mysql -u genee --default-character-set=utf8 -e "update lims2_nankai_sky.user set token=concat(token, '|ldap') where id=$id"`;
}
