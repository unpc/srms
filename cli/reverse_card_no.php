#!/usr/bin/env php
<?php

require "base.php";


Upgrader::echo_fail('=========================');
Upgrader::echo_fail('开始尝试反转人员card_no数据');
Upgrader::echo_fail('=========================');



$users = Q('user[card_no]');

Upgrader::echo_fail('1、初步统计需要反转更新数据的人员总数为: ' . count($users));

$db = Database::factory();

$success = 0;
$fail = 0;

foreach ($users as $user) {

	$sql = sprintf("select card_no from user where id = %d", $user->id);

	$card_no = $db->value($sql);

	$raw_new_no = base_convert($card_no, 10, 16);
	$pad_new_no = str_pad($raw_new_no, 8, '0', STR_PAD_LEFT);
	$new_no = str_split($pad_new_no, 2);

	krsort( $new_no );

	$new_no = hexdec( join('', $new_no) );

	$new_no = sprintf('%u', $new_no);

	//$card_no_s = sprintf('%u', $new_no) & 0xffffff;

	$update = sprintf("update user set card_no = %s ,card_no_s = NULL where id = %d", $new_no, $user->id);


	$ret = $db->query($update);


	if ($ret) {
		$sprintf = sprintf("成功将用户\t%s[%d]\t\t旧的卡号[%s]\t新的卡号[%s]", $user->name, $user->id, $card_no, $new_no);
		$success++;
		Upgrader::echo_success($sprintf);
	}
	else {
		Upgrader::echo_fail(sprintf("用户\t%s[%d]反转失败!\t\t旧的卡号[%s]\t新的卡号[%s]!", $user->name, $user->id, $card_no, $new_no));
		$fail++;
	}

}

Upgrader::echo_fail(sprintf('更新完毕! => 成功人数为:%s 失败人数为:%s', $success, $fail));


