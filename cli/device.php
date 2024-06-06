#!/usr/bin/env php
<?php

include 'base.php';

try {

	$name = $argv[1];
	$channel = $argv[2];
	$device = Device::factory($name, STDIN, STDOUT, $channel);
	$device->run();

}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

