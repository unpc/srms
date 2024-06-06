#!/usr/bin/env php
<?php

require "../base.php";

class Revert_token {
	
	//检测是否需要还原token数据
	static function revert_required() {
		$users = Q('user');
		foreach($users as $user) {
			if (!$user->token) continue;
			preg_match('/\|(\S*)/', $user->token, $matchs);
			if ($matchs) {
				Upgrader::echo_fail("数据需要还原!");
				return TRUE;
			}
		}
		Upgrader::echo_fail("数据不需要还原!");
		return FALSE;
	}
	
	//升级数据库
	public static function update_token() {
		$users = Q("user");
		$total = 0;
		foreach ($users as $user) {
			if (!$user->token) continue;
			preg_match('/\|(\S*)/', $user->token, $matchs);
			if ($matchs) {
				$user->token = preg_replace('/^(\S{6,24})(\|\S*)/', '$1', $user->token);
				$user->save();
				$total++;
			}
		}
		if ($total > 0)
			Upgrader::echo_success("成功还原了 {$total} 条数据!");
		return $total;
	}
	
}



try {
	if (Revert_token::revert_required()) {
		$total = Revert_token::update_token();
		if ($total)
			Upgrader::upgrade_successful();
		else
			Upgrader::echo_fail("数据库中数据已还原，不需在还原");
	}
	else {
		Upgrader::upgrade_none();
	}
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
