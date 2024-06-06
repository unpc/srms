#!/usr/bin/env php
<?php

require "base.php";

try {

	$db = Database::factory();
	$success = (int)$db->query("UPDATE equipment SET is_monitoring = 0");
	if ($success) {
		echo sprintf("所有在线状态仪器都改变为离线状态!\n");
	}
	else {
		echo sprintf("更改状态失败!\n");
	}
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
