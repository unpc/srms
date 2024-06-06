#!/usr/bin/env php
<?php

require "base.php";

try {
	function update_balance($account) {
		$db = ORM_Model::db($account->name());
		//设置account最终总额和余额
		$account->amount = $db->value('SELECT SUM(income) FROM billing_transaction WHERE account_id = %d and status = %d', $account->id, Billing_Transaction_Model::STATUS_CONFIRMED) ?: 0;
		$outcome = $db->value('SELECT SUM(outcome) FROM billing_transaction WHERE account_id = %d', $account->id) ?: 0;
		$account->balance = $account->amount - $outcome;

		$account->save();
	}
	
	
	$accounts = Q("billing_account");
	$error_accounts = [];
	$no_transaction_accounts = [];
	foreach ($accounts as $account) {
		if (!$account->lab->id) {
			$error_accounts[$account->id] = $account->id;
		}
	}
	
	$empty_transactions = Q("eq_charge[!lab]")->to_assoc('id', 'transaction_id');
	
	$delete_lab_transactions = [];
	$charges = Q("eq_charge[lab]");
	foreach ($charges as $charge) {
		if (!$charge->lab->id) {
			$delete_lab_transactions[$charge->id] = $charge->transaction->id; 
		}
	}
	
	$empty_transactions += $delete_lab_transactions;
	
	
	$temp_transactions = [];
	foreach ($empty_transactions as $charge_id => $t_id) {
		$charge = O('eq_charge', $charge_id);
		$transaction = O('billing_transaction', $t_id);
		$dep = $transaction->account->department;
		$user = $charge->user;
		if (!$user->lab->id) {
			$user->lab = Lab_Model::default_lab();
			$user->save();
		}
		$lab = $user->lab;
		$account = O('billing_account', ['department'=>$dep, 'lab'=>$lab]);
		if (!$account->id) $account = $dep->add_account_for_lab($lab);
		if (!$account->id) {
			echo sprintf("	收费记录[%d]转换失败!\n", $charge_id);
			continue;
		}
		
		$outcome = (int)$transaction->outcome;
		$transaction->account = $account;
		if (isset($outcome) && $outcome) $transaction->outcome = $transaction->outcome;
		else {
			$transaction->outcome = 1;
			$transaction->income = 1;
		}
		if ($transaction->save()) {
			
			$charge->lab = $lab;
			$charge->save();
			echo sprintf("收费记录[%d]==财务记录[%d]转换成功!\n", $charge->id, $transaction->id);
		}
	}
	
	
	echo  "\n\n\n";
	
	foreach ($error_accounts as $account_id) {
		$account = O('billing_account', $account_id);
		$transactions = Q("billing_transaction[account={$account}]")->to_assoc('id', 'id');
		if (count($transactions)) {
			foreach ($transactions as $key => $t) {
				$tra = O('billing_transaction', $t);
				if (!$tra->user->id && !$tra->outcome) {
					$tra->delete();
				}
			}
			$tras = Q("billing_transaction[account={$account}]")->to_assoc('id', 'id');
			if (count($tras)) {
				echo sprintf("错误帐号[%d]下存在以下事务: %s\n", $account->id, join(', ', $tras));
				continue;
			}
		}
		echo "==============================\n";
		echo sprintf("错误帐号[%d]下不存在任务事务，故删除\n", $account->id);
		echo "==============================\n";
		$account->delete();
	}
	
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
	
