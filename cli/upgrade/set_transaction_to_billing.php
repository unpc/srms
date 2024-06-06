#!/usr/bin/env php
<?php

require "../base.php";

class Upgrade_Billing_transaction {
	
	//检测是否需要合法升级
	 static function upgrade_required() {
		$transactions = Q('transaction');
		
		//数据库中不存在事务或者表都不存在，则无法升级
		if (!count($transactions)) {
			Upgrader::echo_separator();
			Upgrader::echo_title('数据库中不存在实验室事务!不可升级!');
			return FALSE;			
		}
		$billing_transactions = Q('billing_transaction');
		if (!count($billing_transactions)) {
			Upgrader::echo_separator();
			Upgrader::echo_title('数据库中部门事务缺少!需要升级!');
			return TRUE;
		}
		//循环比对其中值是否相同
		foreach ($transactions as $transaction) {
			if (!$transaction->lab->id)
				continue;
			$sign = Upgrade_Billing_transaction::check_data($transaction);
			if (!$sign) {
				Upgrader::echo_separator();
				Upgrader::echo_title('数据库中数据不符合!需要升级!');
				return TRUE;
			}
		}
		return FALSE;
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
		$total = 0;
		$transactions = Q('transaction');
		foreach($transactions as $transaction) {
			$lab = $transaction->lab;
			if (!$lab->id)
				continue;
			$sign = Upgrade_Billing_transaction::check_data($transaction);
			if (!$sign) {
				$lab = $transaction->lab;
				$account = Q("billing_account[lab=$lab][department=$department]")->current();
				$billing_transaction = O('billing_transaction');
				$billing_transaction->account = $account;
				$billing_transaction->reference = $transaction->reference;
				$billing_transaction->status = $transaction->status;
				$billing_transaction->income = $transaction->income;
				$billing_transaction->outcome = $transaction->outcome;
				$billing_transaction->balance = $transaction->balance;
				$billing_transaction->invoice_no = $transaction->invoice_no;
				$billing_transaction->ctime = $transaction->ctime;
				$billing_transaction->mtime = $transaction->mtime;
				$billing_transaction->user = Q("eq_charge[transaction=$transaction]")->current()->user;
				$billing_transaction->description = $transaction->description;
				$billing_transaction->save();
				$total++;
			}
		}
		Upgrader::echo_separator();
		Upgrader::echo_success("数据库升级完毕!");
		Upgrader::echo_success("总共升级{$total}条目!");
	}
	
	//检测数据
	static function check_data($transaction) {
		$billing_transactions = Q('billing_transaction');
		$arr1 = [
			'lab'=>$transaction->lab->id,
			'status'=>$transaction->status,
			'income'=>$transaction->income,
			'outcome'=>$transaction->outcome,
			'balance'=>$transaction->balance,
			'ctime'=>$transaction->ctime,
		];
		foreach ($billing_transactions as $fin) {
			$arr2 = [
				'lab'=>$fin->account->lab->id,
				'status'=>$fin->status,
				'income'=>$fin->income,
				'outcome'=>$fin->outcome,
				'balance'=>$fin->balance,
				'ctime'=>$fin->ctime,
			];
			if ($arr1 == $arr2) {
				return TRUE;
			}
		}
		return FALSE;
	}
}


$department = Q('billing_department[name=default]')->current();
if (!$department->id)  {
	Upgrader::echo_separator();
	Upgrader::echo_title('创建单模式下必须的default财务部门...');
	$department = O('billing_department');
	$department->name = 'default';
	$department->save();
}
$labs = Q('lab');
foreach ($labs as $lab) {
	$account = Q("billing_account[lab=$lab][department=$department]")->current();
	if (!$account->id ) {
		Upgrader::echo_title('给'.$lab->name.'实验室创建default财务部门下的帐号!');
		$account = O('billing_account');
		$account->lab = $lab;
		$account->department = $department;
		$account->balance = $lab->credit_balance ? $lab->credit_balance : 0;
		$account->save();
	}
}

try {
	if (Upgrade_Billing_transaction::upgrade_required()) {
		Upgrade_Billing_transaction::backup();
		Upgrade_Billing_transaction::upgrade();
		Upgrader::upgrade_successful();
	}
	else {
		Upgrader::upgrade_none();
	}
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
