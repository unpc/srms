#!/usr/bin/env php
<?php

require "../base.php";

$u = new Upgrader;

$u->check = function() {
	if (!Module::is_installed('treenote')) {
		$this->echo_title('Treenote没有安装');
		return FALSE;
	}
	return TRUE;
};

//升级脚本
$u->upgrade = function() {

	$total = 0;	
	foreach (Q('tn_note') as $note) {
		Upgrader::echo_title("更新 {$note->title} 的项目...");
		$note->project = $note->task->project;
		$note->save();
		$total++;
	}
	
	$this->echo_separator();
	$this->echo_success("总共升级{$total}条目!");
};

$u->run();
