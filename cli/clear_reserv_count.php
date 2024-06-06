#!/usr/bin/env php
<?php

require "base.php";

try {

	foreach (Q('user_violation') as $user) {
		$user->eq_miss_count = 0;
		$user->eq_overtime_count = 0;
		printf("用户%s[%d]预约超时和爽约次数请空\n", $user->name, $user->id);
		$user->save();
	}
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

