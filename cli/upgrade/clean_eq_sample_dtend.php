#!/usr/bin/env php
<?php
require "../base.php";
$u = new Upgrader;

// 检查是否需要升级
$u->check = function() {
	$has_dtend_statuses = join(',', [EQ_Sample_Model::STATUS_APPROVED]);
	$dirty_eq_samples = Q("eq_sample[status!={$has_dtend_statuses}][dtend>0]");
	
	if ($dirty_eq_samples->total_count() > 0) {
		return TRUE;
	}
	return FALSE;
};

// 备份
$u->backup = function() {
	$dbfile = LAB_PATH . 'private/backup/clean_eq_sample_dtend.sql';
	File::check_path($dbfile);

	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "备份数据库");

	$db = Database::factory();
	return $db->snapshot($dbfile);
};

// 恢复
$u->restore = function() {
	$dbfile = LAB_PATH . 'private/backup/clean_eq_sample_dtend.sql';

	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "恢复数据库");
	$db = Database::factory();
	$db->restore($dbfile);
};

// 升级
$u->upgrade = function() {
	$total = 0;
	$has_dtend_statuses = join(',', [EQ_Sample_Model::STATUS_APPROVED]);
	$dirty_eq_samples = Q("eq_sample[status!={$has_dtend_statuses}][dtend>0]");
	foreach ($dirty_eq_samples as $dirty_eq_sample) {
		$dirty_eq_sample->dtend = 0;
		$dirty_eq_sample->save();

		$total++;
	}

	Upgrader::echo_success('升级完毕');
	Upgrader::echo_success("总共升级{$total}条目!");
};

// 验证升级成果
$u->verify = function() {
	$has_dtend_statuses = join(',', [EQ_Sample_Model::STATUS_APPROVED]);
	$dirty_eq_samples = Q("eq_sample[status!={$has_dtend_statuses}][dtend>0]");
	if ($dirty_eq_samples->total_count() == 0) {
		return TRUE;
	}
	return FALSE;
};

$u->post_upgrade = function(){};

$u->run();