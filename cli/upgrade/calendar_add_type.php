#!/usr/bin/env php
<?php

require "../base.php";

class Upgrade_Calendar_Type {
	
	//检测是否需要升级
	public static function upgrade_required() {
		$cal_schedule = Q("calendar[type=schedule]")->length();
		$cal_eq_incharge = Q("calendar[type=eq_incharge]")->length();
		if ($cal_schedule || $cal_eq_incharge) {
			return FALSE;
		}
		return TRUE;
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
	public static function upgrade() {
		//$total用来计数,统计一共升级了多少条数据
		$total = 0;
		$collections = Q("calendar");
		$types = ['user'=>'schedule', 
					   'eq_incharge'=>'eq_incharge'
					];
			
		foreach ($collections as $object) {
			if (!$object->parent->id) continue;
			$name = $object->parent->name();
			if (in_array($name, array_keys($types)) && !(bool)$object->type) {
				Upgrader::echo_title("upgrading {$object->name}......");
				if ($name == 'eq_incharge') {
					Upgrader::echo_title("upgrading parent $name=>user");
					$user = O('user', $object->parent->id);
					$object->parent = $user;
				}
				$type = $types[$name];
				Upgrader::echo_title("upgrading type=>$type");
				$object->type = $type;
				$object->save();
				$total++;
				Upgrader::echo_success("upgrading ok!");
			}
		}
		Upgrader::echo_separator();
		Upgrader::echo_success("upgrading total {$total}!");
	}
}

if (Upgrade_Calendar_Type::upgrade_required()) {
	Upgrade_Calendar_Type::backup($argv);
	Upgrade_Calendar_Type::upgrade();
	Upgrader::upgrade_successful();
}
else {
	Upgrader::upgrade_none();
}

