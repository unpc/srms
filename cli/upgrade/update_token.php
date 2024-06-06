#!/usr/bin/env php
<?php

require "../base.php";

class Upgrade_token {
	
	//检测是否需要合法升级
	static function upgrade_required($data) {
		$users = Q('user');
		foreach($users as $user) {
			if (!$user->token) continue;
			preg_match('/\|(\S*)/', $user->token, $matchs);
			if (!$matchs) {
				Upgrader::echo_fail("数据库中token版本信息低,需要升级!");
				return TRUE;
			}
			else {
				if (!in_array($matchs[1], array_keys($data))) {
					Upgrader::echo_fail("数据库中token版本错误,需要更新升级!");
					return TRUE;
				}
			}
		}
	}
	
	//备份数据库
	public static function backup($argv=[]) {
		$host = Upgrader::$HOST;
		$username = Upgrader::$USERNAME;
		$password = Upgrader::$PASSWORD;
		
		//将数据备份到 LAB_PATH 下面的 backup 文件夹下面
		$dir = LAB_PATH . 'private/backup/';
		if (!file_exists($dir)) {
			@mkdir($dir);
		}
		$database = Config::get('database.prefix').LAB_ID;
		//生成表名
		if ($argv > 1) {
			array_shift($argv);
			$table = implode(' ', $argv);
			$table_name = implode('+', $argv);
		}
		if ($table) {
			//只是备份数据库表
			$dbfile = $dir.$database."\($table_name\)".date('Y-m-d', time()).'.sql';
			$command = "mysqldump -h $host -u $username -p $password $database $table > $dbfile";
			$text = "备份数据库($database)表($table)...OK!";
		}
		else {
			//备份整个数据库
			$dbfile = $dir.$database.date('Y-m-d', time()).'.sql';
			$command = "mysqldump -h $host -u $username -p $password $database > $dbfile";
			$text = "备份数据库($database)...OK!\n";
		}
		system($command);
		Upgrader::echo_success($text);
	}
	
	//升级数据库
	public static function update_token($data) {
		$users = Q("user");
		$total = 0;
		$suffix = Config::get('auth.default_backend', 'database');
		foreach ($users as $user) {
			if (!$user->token) continue;
			preg_match('/\|(\S*)/', $user->token, $matchs);
			if (!$matchs) {
				$user->token = $user->token."|".$suffix;
				$user->save();
				$total++;
				continue;
			}
			$new_data = array_keys($data);
			if (!in_array($matchs[1], $new_data)) {
				$user->token = preg_replace('/^(\S{6,24}\|)(\S*)/', '${1}'.$suffix, $user->token);
				$user->save();
				$total++;
				continue;
			}
		}
		if ($total > 0)
			Upgrader::echo_success("成功升级了 {$total} 条数据!");
		return $total;
	}
	
}

$backends = Config::get('auth.backends');
$backends_titles = [];
foreach ($backends as $key=>$values) {
	$backends_titles[$key] = $values['title'];
}
$data = $backends_titles;
if (!count($data)) $data = ['database' => '本地登录'];


try {
	if (Upgrade_token::upgrade_required($data)) {
		//Upgrade_token::backup();
		$total = Upgrade_token::update_token($data);
		if ($total > 0)
			Upgrader::upgrade_successful();
		else
			Upgrader::echo_fail("未成功升级数据，请检查数据库中数据是否正确。");
	}
	else {
		Upgrader::upgrade_none();
	}
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
