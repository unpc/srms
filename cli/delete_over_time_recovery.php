#!/usr/bin/env php
<?php

require "base.php";


try {

	$now = time();
	Q("recovery[overdue>0][overdue<={$now}]")->delete_all();
	
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
