#!/usr/bin/env php
<?php
/*
	将所有用户的隐私设置都设为 仅自己可见(xiaopei.li@2012-09-12)
*/

require 'base.php';

$i = 0;

foreach (Q('user') as $user) {
	$user->privacy = 1; // 1 为仅自己可见
	$user->save();

	if (++$i % 50 == 0) {
		echo '.';
	}

	if (++$I % 500 == 0) {
		echo "\n";
	}

}

echo "\n";
