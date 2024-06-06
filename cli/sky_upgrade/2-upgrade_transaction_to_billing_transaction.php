#!/usr/bin/env php
<?php

require "../base.php";

$time = Date::format(NULL, 'YmdHis');

$dbfile = "$time-2-upgrade_transaction_to_billing_transaction.sql";

$db = Database::factory();
$db->snapshot($dbfile);

Upgrader::echo_title('备份');

//准备财务部门
$department = Q('billing_department')->current();
if (!$department->id)  {
	Upgrader::echo_separator();
	Upgrader::echo_title('创建单模式下必须的default财务部门...');
	$department = O('billing_department');
	$department->name = '财务中心'; /* 更改默认财务部门名称(xiaopei.li@2011.09.20) */
	if ($department->save()) {
		Upgrader::echo_success('默认财务中心建立成功');
	}
	else {
		Upgrader::echo_fail('默认财务中心建立失败');
		exit(1);
	}
}

$department = ORM_Model::refetch($department);

//实验室创建财务帐号
$accounts = [];
$labs = Q('lab');
foreach ($labs as $lab) {
	$account = Q("billing_account[lab=$lab][department=$department]")->current();
	if (!$account->id ) {
		$account = $department->add_account_for_lab($lab);

		/*
		Upgrader::echo_title('给'.$lab->name.'实验室创建default财务部门下的帐号!');
		$account = O('billing_account');
		$account->lab = $lab;
		$account->balance = $lab->credit_balance ? : 0;
		$account->department = $department;
		$account->save();
		*/

		if ($account->id) {
			$account->balance = $lab->credit_balance ? : 0;
			$account->save();
			Upgrader::echo_success('给'.$lab->name.'实验室创建 default 财务部门下的帐号成功!');
		}
		
	}
	$accounts[$lab->id] = $account;
}

//重命名transaction -> billing_transaction
Upgrader::echo_title('重命名transaction->billing_transaction');
$db = Database::factory();
$db->query('ALTER TABLE transaction ADD COLUMN `account_id` bigint(20) NOT NULL DEFAULT "0"');

// commit 539a86 已将 parent 属性删除
//$db->query('ALTER TABLE transaction ADD COLUMN `parent_name` varchar(40) NOT NULL DEFAULT ""');
//$db->query('ALTER TABLE transaction ADD COLUMN `parent_id` bigint(20) NOT NULL DEFAULT "0"');
$db->query('RENAME TABLE transaction TO billing_transaction');
$db->query('RENAME TABLE _p_transaction TO _p_billing_transaction'); /* 还需重命名附加属性表(xiaopei.li@2011.09.20) */

//更新billing_transaction的lab_id列为account_id
Upgrader::echo_title('add account info for billing_transaction');
foreach ($accounts as $lab_id => $account) {

	$query = strtr("UPDATE billing_transaction SET account_id=%account_id where lab_id=%lab_id", [
					   '%account_id' => $db->quote($account->id),
					   '%lab_id' => $db->quote($lab_id),
					   ]);

	Upgrader::echo_title($query);
	$db->query($query);
}

/*
$fin_transactions = $db->query('SELECT * FROM fin_transaction');
while ($billing_transaction = $billing_transactions->row()) {
	$id = $billing_transaction->id;
	$lab_id = $billing_transaction->lab_id;
	$account_id = $accounts[$lab_id]->id;
	$db->query("UPDATE billing_transaction SET account_id={$account_id} WHERE id={$id}");
	Upgrader::echo_success("$id\t更新成功");
}
*/

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
/*
Upgrader::echo_title('billing_transaction到eq_charge的引用');
$charges = Q('eq_charge');
foreach ($charges as $charge) {
	$transaction = $charge->transaction;
	$db->query("UPDATE billing_transaction SET parent_name='eq_charge', parent_id={$charge->id} WHERE id={$transaction->id}");
	// commit 539a86 已将 parent 属性删除
	$transaction->parent = $charge;
	if ($transaction->save()) {
		Upgrader::echo_success("{$charge->id}:{$transaction->id}");
	}
	else {
		Upgrader::echo_fail("{$charge->id}:{$transaction->id}");
	}
}
*/

echo 'done';
