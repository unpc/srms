#!/usr/bin/env php
<?php

require "../base.php";

//将数据备份到 LAB_PATH 下面的 backup 文件夹下面
$dir = LAB_PATH . 'private/backup/';
if (!file_exists($dir)) {
	@mkdir($dir);
}
$database = Config::get('database.prefix').LAB_ID;

$host = 'localhost';
$username = 'genee';
$password = '';

//备份整个数据库
$dbfile = $dir.$database.date('Y-m-d', time()).'.sql';
if (!$password) {
	$command = "mysqldump -h $host -u $username $database > $dbfile";
}
else {
	$command = "mysqldump -h $host -u $username -p $password $database > $dbfile";
}
$text = "备份数据库($database)...OK!\n";
system($command);
Upgrader::echo_success($text);

//准备财务部门
$department = Q('billing_department[name=default]')->current();
if (!$department->id)  {
	Upgrader::echo_separator();
	Upgrader::echo_title('创建单模式下必须的default财务部门...');
	$department = O('billing_department');
	$department->name = 'default';
	$department->save();
}

//实验室创建财务帐号
$accounts = [];
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
	$accounts[$lab->id] = $account;
}

//重命名transaction -> billing_transaction
Upgrader::echo_title('重命名transaction->billing_transaction');
$db = Database::factory();
$db->query('ALTER TABLE transaction ADD COLUMN `account_id` bigint(20) NOT NULL DEFAULT "0"');
$db->query('ALTER TABLE transaction ADD COLUMN `parent_name` varchar(40) NOT NULL DEFAULT ""');
$db->query('ALTER TABLE transaction ADD COLUMN `parent_id` bigint(20) NOT NULL DEFAULT "0"');
$db->query('RENAME TABLE transaction TO billing_transaction');

//更新billing_transaction的lab_id列为account_id
Upgrader::echo_title('add account info for billing_transaction');
$billing_transactions = $db->query('SELECT * FROM billing_transaction');
while ($billing_transaction = $billing_transactions->row()) {
	$id = $billing_transaction->id;
	$lab_id = $billing_transaction->lab_id;
	$account_id = $accounts[$lab_id]->id;
	$db->query("UPDATE billing_transaction SET account_id={$account_id} WHERE id={$id}");
	Upgrader::echo_success("$id\t更新成功");
}

//更新每个lab的amount信息
Upgrader::echo_title('更新实验室amount信息');
foreach ($labs as $lab) {
	$account = $accounts[$lab->id];
	$sum = $db->value("SELECT SUM(income) FROM billing_transaction WHERE account_id={$account->id}");
	$account->amount = $sum;
	$account->save();
	Upgrader::echo_success("{$lab->name}\t{$sum}");
}

//billing_transaction到eq_charge的引用
Upgrader::echo_title('billing_transaction到eq_charge的引用');
$charges = Q('eq_charge');
foreach ($charges as $charge) {
	$transaction = $charge->transaction;
	$db->query("UPDATE billing_transaction SET parent_name='eq_charge', parent_id={$charge->id} WHERE id={$transaction->id}");
	Upgrader::echo_success("{$charge->id}:{$transaction->id}");
}
