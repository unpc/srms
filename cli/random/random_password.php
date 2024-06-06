#!/usr/bin/env php
<?php

require "base.php";


try {

	echo Misc::random_password(8)."\n";
	
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

