#!/usr/bin/env php
<?php

require "base.php";


try {

	$now = time();
	$trainings = Q("ue_training[atime=1~{$now}]");
	foreach ($trainings as $training) {
		$training->delete();
	}
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

