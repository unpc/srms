#!/usr/bin/env php
<?php

   /*
	* @file	   upgrade_user_auditor.php
	* @author  Kai.Wu<kai.wu@geneegroup.com>
	* @date	   2011.10.27
	*
	* usage: SITE_ID=cf LAB_ID=test ./upgrade_user_auditor.php
	*/

require '../base.php';

$u = new Upgrader;

//检查是否需要进行系统升级
$u->check = function() {
	$db = Database::factory();
	$auditor_col_existed = $db->value('SHOW COLUMNS FROM `user` WHERE field LIKE "auditor_id"');
	if ($auditor_col_existed) return TRUE;
	return FALSE;
};

//数据库备份
$u->backup = function() {
	$dbfile = LAB_PATH . 'private/backup/user_auditor.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
	$db = Database::factory();
	return $db->snapshot($dbfile);
};

//升级脚本
$u->upgrade = function() {
	$users = Q('user');
	$db = Database::factory();

	$user_count = 0;
	foreach($users as $user) {
		$user->auditor = P($user)->get('auditor', TRUE);
		if($user->save()) {
			$user_count ++;
			echo T('%user[%user_id]的审批者更新成功\n', ['%user' => $user->name, '%user_id' => $user->id]);
		}
	}
	echo T('共有%user_count个用户的审批者更新成功\n', ['%user_count' => $user_count]);
};

//恢复数据
$u->restore = function() {
	$dbfile = LAB_PATH . 'backup/user_auditor.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
	$db = Database::factory();
	$db->restore($dbfile);
};

$u->run();
