#!/usr/bin/env php
<?php

require "base.php";


try {

	$now = time();
	$banneds = Q("eq_banned[atime][atime <= {$now}]");
	foreach($banneds as $banned) {
		$banned->delete();
	}
	
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

