#!/usr/bin/env php
<?php
require '../base.php';

$time = Date::format(NULL, 'YmdHis');
$dbfile = "$time-9-upgrade-eq.sql";
$db = Database::factory();
$db->snapshot($dbfile);

$db->query("ALTER TABLE `equipment` CHANGE `ref_no` `ref_no` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT ''");
$db->query("update `equipment` set ref_no = NULL where not ref_no");

foreach (Q('equipment') as $eq) {
	$eq->name_abbr = PinYin::code($eq->name);
	$eq->save();
}
