#!/usr/bin/env php
<?php

require "base.php";

try {
	$now = time();
	$auths = Q("um_auth[atime=1~{$now}]");
	foreach ($auths as $auth) {
		$auth->delete();
	}
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}