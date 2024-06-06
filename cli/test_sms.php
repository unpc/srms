<?php
require 'base.php';

if ($argc < 2) {
	die("usage:\n  test_sms.php sender_user_id [receiver_user_id]\n");
}

$sender = O('user', $argv[1]);

if (!$sender->id) {
	die('sender 不存在');
}

if ($argv[2]) {
	$receiver = O('user', $argv[2]);

	if (!$receiver->id) {
		die('receiver 不存在');
	}
}
else {
	$receiver = $sender;
}

Notification::send('sms.test', $sender, [$receiver]);
