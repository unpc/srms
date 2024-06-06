#!/usr/bin/env php
<?php

// 遍历用户, 在用户账号已同步到数据库中的基础上, 使用 synjones 验证用户身份(通过卡号能找到, 且找到的姓名同用户姓名)后, 修改用户的 token 为一卡通(xiaopei.li@2012-01-10)

require '../base.php';

// TODO 待山大服务器可以连接一卡通服务器后, 应删除下面两行, 用配置中的地址
$server = Config::get('lab.sdu_synjones_server');
$port = Config::get('lab.sdu_synjones_port');

$client = new Synjones($server, $port);

$n_changed = 0;

foreach (Q('user') as $user) {
	if (!$user->ref_no) {
		continue;
	}

	if (!$client->query_user('student_code', $user->ref_no)) {
		// error_log($user->name . ': ' . $client->get_last_error()); // uncomment to debug
		continue;
	}

	$info = $client->get_last_response();

	if (change_user_token($user, $info)) {
		$n_changed++;
		// error_log($user->name . ": ok \n"); // uncomment to debug
	}
}

function change_user_token($user, $info) {
	/*
	if (trim($info['name']) == trim($user->name)  // 学号填对了么?
		&& $user->synjones_account_no) {	// 且用户有 synjones_account_no
	*/
	if (trim($info['name']) == trim($user->name)) {  // 学号填对了么?
		$new_full_token = Auth::make_token($user->ref_no, 'synjones');
		
		if ($user->token != $new_full_token) {
			if (!O('user', ['token' => $new_full_token])->id) {
				$user->token = $new_full_token;
				return $user->save();
			}
		}
	}
}
