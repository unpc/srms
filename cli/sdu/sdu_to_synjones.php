#!/usr/bin/env php
<?php
require "../base.php";
 
$u = new Upgrader;
 
//检查是否需要升级脚本
$u->check = function() {
	$db = Database::factory();

	$has_sdu_account_no = $db->value('show columns from user where field like "sdu_account_no"');
	$has_synjones_account_no = $db->value('show columns from user where field like "synjones_account_no"');
	
	if ($has_sdu_account_no && !$has_synjones_account_no) {
		return TRUE;
	}
 
	return FALSE;
};
 
//备份脚本
$u->backup = function() {
 
	$dbfile = LAB_PATH.'private/backup/sdu_to_synjones.sql';
	File::check_path($dbfile);
 
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "备份数据库表");
 
	$db = Database::factory();
	return $db->snapshot($dbfile);
 
};
 
//恢复脚本
$u->restore = function() {
};
 
//升级脚本
$u->upgrade = function() {
	$db = Database::factory();

	$db->query("alter table user change sdu_account_no synjones_account_no int");
	Upgrader::echo_success("数据库升级完毕!");
};
 
//升级验证脚本
$u->verify = function() {
	$db = Database::factory();

	$has_sdu_account_no = $db->value('SHOW COLUMNS FROM user WHERE field LIKE "sdu_account_no"');
	$has_synjones_account_no = $db->value('SHOW COLUMNS FROM user WHERE field LIKE "synjones_account_no"');
	
	if (!$has_sdu_account_no && $has_synjones_account_no) {
		return TRUE;
	}
 
	return FALSE;
};
 
//升级后脚本
$u->post_upgrade = function() {
};
 
$u->run();
