#!/usr/bin/env php
<?php

require "base.php";


$valid_engines = [
	'MYISAM', 'InnoDB'
];

$engine = $argv[1];
if (!in_array($engine, $valid_engines)) $engine = reset($valid_engines);

try {

	$db = Database::factory();
	// ALTER TABLE `achievement`  ENGINE =  InnoDB
	$db->query('SET GLOBAL storage_engine = "%s"', $engine);
	$rs = $db->query('SHOW TABLES');
	while ($r = $rs->row('num')) {
		$db->query('ALTER TABLE `%s` ENGINE = "%s"', $r[0], $engine);
	}

	
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

