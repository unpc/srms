#!/usr/bin/env php
<?php

require "../base.php";

$u = new Upgrader;

//检查是否需要升级脚本
$u->check = function() {
	$db = Database::factory();
	$incharge_id = $db->value('SELECT MAX(incharge_id) FROM equipment LIMIT 1');
	if ($incharge_id) {
		return TRUE;
	}

	return FALSE;
};

//备份脚本
$u->backup = function() {

	$dbfile = LAB_PATH.'private/backup/incharge_multi_user.sql';
	File::check_path($dbfile);

	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "备份数据库表");
	
	$db = Database::factory();
	return $db->snapshot($dbfile);
	
};

//恢复脚本
$u->restore = function() {
	$equipments = Q('equipment');
	foreach ($equipments as $equipment) {
		$equipment->disconnect($incharge, 'incharge');
	}

	$dbfile = LAB_PATH . 'private/backup/incharge_multi_user.sql';

	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "恢复数据库表");
	$db = Database::factory();
	$db->restore($dbfile);	
};

//升级脚本
$u->upgrade = function() {
	//$total用来计数,统计一共升级了多少条数据
	$total = 0;
	$equipments = Q('equipment');
	$db = Database::factory();

	foreach ($equipments as $equipment) {
		$incharge_id = $db->value("SELECT incharge_id FROM equipment WHERE id=%d", $equipment->id);
		$incharge = O('user', $incharge_id);
		//建立关系
		Upgrader::echo_title("升级{$equipment->name}(id:{$equipment->id})的incharge...");
		$equipment->connect($incharge, 'incharge');
		
		Upgrader::echo_title("将原来的负责人改为联系人...");
		$equipment->contact = $incharge;
		$equipment->save();
		
		Upgrader::echo_success("操作完毕");
		$total++;
	}
	
	Upgrader::echo_separator();
	Upgrader::echo_success("数据库升级完毕!");
	Upgrader::echo_success("总共升级{$total}条目!");


	Upgrader::echo_separator();
	Upgrader::echo_title("删除equipment表中的incharge列和索引...");

	$sql = 'ALTER TABLE `equipment` DROP COLUMN incharge_id';

	if ($db->query($sql)) {
		Upgrader::echo_success("incharge列删除成功!");
	}
	else {
		Upgrader::echo_fail("incharge列删除失败!");	
	}
};

//升级验证脚本
$u->verify = function() {
	$equipments = Q('equipment');
	$db = Database::factory();
	foreach ($equipments as $equipment) {
		$incharge_id = $db->value("SELECT incharge_id FROM equipment WHERE id=%d", $equipment->id);
		$incharge = O('user', $incharge_id);
		$new_incharge = Q("$equipment user.incharge")->current();
		if ($incharge->id != $new_incharge->id) {
			return FALSE;
		}
	}
	return TRUE;
};

//升级后脚本
$u->post_upgrade = function() {
};

$u->run();

