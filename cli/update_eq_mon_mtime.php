#!/usr/bin/env php
<?php
require 'base.php';

$cache = Cache::factory();
$now = Date::time();
$exp_time = $now - 60; 
if ($cache->get('equipment.monitoring_mtime') < $exp_time) {
	$SQL = "UPDATE equipment SET is_monitoring=0 WHERE is_monitoring=1 AND is_monitoring_mtime<{$exp_time}";
	ORM_Model::db('equipment')->query($SQL);
	$cache->set('equipment.monitoring_mtime', $now);
}

