#!/usr/bin/env php
<?php

   /*
	* @file	   upgrade_lab_auditor.php
	* @author  Kai.Wu<kai.wu@geneegroup.com>
	* @date	   2011.10.27
	*
	* usage: SITE_ID=cf LAB_ID=test ./upgrade_lab_auditor.php
	*/

require '../base.php';

$u = new Upgrader;

//检查是否需要进行系统升级
$u->check = function() {
	$db = Database::factory();
	$auditor_col_existed = $db->value('SHOW COLUMNS FROM `lab` WHERE field LIKE "auditor_id"');
	if ($auditor_col_existed) return TRUE;
	return FALSE;
};

//数据库备份
$u->backup = function() {
	$dbfile = LAB_PATH . 'private/backup/lab_auditor.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
	$db = Database::factory();
	return $db->snapshot($dbfile);
};

//升级脚本
$u->upgrade = function() {
	$labs = Q('lab');
	$db = Database::factory();

	$lab_count = 0;
	foreach ($labs as $lab) {
		$lab->auditor = P($lab)->get('auditor', TRUE);
		if ($lab->save()) {
			$lab_count ++;
			echo T('%lab[%lab_id]的审批者更新成功\n', ['%lab' => $lab->name, '%lab_id' => $lab->id]);
		}
	}
	echo T('共有%lab_count条记录更新成功\n', ['%lab_count' => $lab_count]);
};

//恢复数据
$u->restore = function() {
	$dbfile = LAB_PATH . 'backup/lab_auditor.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
	$db = Database::factory();
	$db->restore($dbfile);
};

$u->run();