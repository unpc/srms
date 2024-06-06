#!/usr/bin/env php
<?php
/*
 * 试运行转为正式运行时，备份试运行数据以备不时之需；同时，将试运行产生的部分数据从正式运行数据库中清除
 */
require "base.php";
$now = time();
$lab_id = $_SERVER['LAB_ID'];
//备份数据库
$file = LAB_PATH . 'logs/' . $lab_id . '.' . date('Y-m-d.H:i:s', $now) . '.sql';
Log::add("备份数据库到\t$file ", 'switch_to_formal_run');
exec("mysqldump -u genee --default-character-set=utf8 lims2_{$lab_id} > $file", $out, $status);
if ($status) {
	die("备份数据库{$file}失败 ");
}
//正式运行的日期时间字符串
$runtime = '20110901';
$runtime = strtotime($runtime);
$str_runtime = date('Y-m-d H:i:s', $runtime);
// delete runtime之前的eq_record
Log::add("处理dtend小于{$str_runtime}的eq_record: ", 'switch_to_formal_run');
$records = Q("eq_record[dtend<=$runtime][dtend!=0]");
if (!count($records)) {
	Log::add("\t没有符合条件的eq_record记录 ", 'switch_to_formal_run');
}
else {
	$i = 0;
	foreach ($records as $record) {
		Log::add("\tDelete\t{$record}\t{$record->equipment}\t{$record->user} ", 'switch_to_formal_run');
		$record->delete();
		$i++;
	}
	Log::add("\t共删除{$i}条eq_record ", 'switch_to_formal_run');
}
// 修改跨越runtime的eq_record
Log::add("处理跨越{$str_runtime}的eq_record: ", 'switch_to_formal_run');
$records = Q("eq_record[dtstart<$runtime]");
if (!count($records)) {
	Log::add("\t没有符合条件的eq_record记录 ", 'switch_to_formal_run');
}
else {
	$i = 0;
	foreach ($records as $record) {
		Log::add("\tEdit\t{$record}\t{$record->equipment}\t{$record->user} ", 'switch_to_formal_run');
		$record->dtstart = $runtime;
		$record->save();
		$i++;
	}
	Log::add("\t共修改{$i}条eq_record ", 'switch_to_formal_run');
}
// 修改仪器的使用状态，根据仪器的使用记录
$equipments = Q('equipment');
foreach ($equipments as $eq) {
	$eq->is_monitoring = false;
	$c_r = Q("eq_record[equipment={$eq}][dtstart<{$now}][dtend=0]:limit(1)")->current();
	if ($c_r->id) {
		$eq->is_using = true;
	}
	else {
		$eq->is_using = false;
	}
	$eq->save();
}
// delete runtime前的cal_component
Log::add("处理dtend小于{$str_runtime}的cal_component: ", 'switch_to_formal_run');
$components = Q("cal_component[dtend<=$runtime]");
if (!count($components)) {
	Log::add("\t没有符合条件的cal_component记录 ", 'switch_to_formal_run');
}
else {
	$i = 0;
	foreach ($components as $component) {
		Log::add("\tDelete\t{$component}\t{$component->calendar->parent->name}[{$component->calendar->parent}] ", 'switch_to_formal_run');
		$component->delete();
		$i ++;
	}
	Log::add("\t共删除{$i}条cal_component ", 'switch_to_formal_run');
}
// 修改跨越runtime的cal_component
Log::add("处理跨越{$str_runtime}的cal_component: ", 'switch_to_formal_run');
$components = Q("cal_component[dtstart<$runtime][dtend>$runtime]");
if (!count($components)) {
	Log::add("\t没有符合条件的cal_component记录 ", 'switch_to_formal_run');
}
else {
	$i = 0;
	foreach ($components as $component) {
		Log::add("\tEdit\t{$component}\t{$component->calendar->parent->name}[{$component->calendar->parent}] ", 'switch_to_formal_run');
		$component->dtstart = $runtime;
		$component->save();
		$i++;
	}
	Log::add("\t共修改{$i}条cal_component ", 'switch_to_formal_run');
}
// delete runtime前的eq_sample
Log::add("处理dtend小于{$str_runtime}的eq_sample: ", 'switch_to_formal_run');
$samples = Q("eq_sample[dtstart<$runtime][dtend<=$runtime]");
if (!count($samples)) {
	Log::add("\t没有符合条件的eq_sample记录 ", 'switch_to_formal_run');
}
else {
	$i = 0;
	foreach ($samples as $sample) {
		Log::add("\tDelete\t{$sample}\t{$sample->equipment}\t{$sample->user} ", 'switch_to_formal_run');
		$sample->delete();
		$i++;
	}
	Log::add("\t共删除{$i}条eq_sample ", 'switch_to_formal_run');
}
// 修改跨越runtime的eq_sample
Log::add("处理跨越{$str_runtime}的eq_sample: ", 'switch_to_formal_run');
$samples = Q("eq_sample[dtstart<$runtime][dtend>$runtime]");
if (!count($samples)) {
	Log::add("\t没有符合条件的eq_sample记录 ", 'switch_to_formal_run');
}
else {
	$i = 0;
	foreach ($samples as $sample) {
		Log::add("\tDelete\t{$sample}\t{$sample->equipment}\t{$sample->user} ", 'switch_to_formal_run');
		$sample->dtstart = $runtime;
		$sample->save();
		$i++;
	}
	Log::add("共修改{$i}的eq_sample", 'switch_to_formal_run');
}
// 清空用户超时和爽约的计数
$users = Q('user_violation');
$i = 0;
Log::add("清除所有用户超时和爽约的计数信息 ", 'switch_to_formal_run');
foreach ($users as $user) {
	if (!$user->eq_miss_count && !$user->eq_overtime_count) {
		continue;
	}
	$user->eq_miss_count = 0;
	$user->eq_overtime_count = 0;
	$user->save();
	Log::add("\t$user\t清空超时和爽约计数 ", 'switch_to_formal_run');
	$i++;
}
// 清除runtime前的transaction记录
$billing_transactions = Q("billing_transaction[ctime<=$runtime]");
Log::add("处理{$str_runtime}前的billing_transaction: ", 'switch_to_formal_run');
if (!count($billing_transactions)) {
	Log::add("\t没有需要处理的billing_transaction记录 ", 'switch_to_formal_run');
}
else {
	$i = 0;
	foreach ($billing_transactions as $billing_transaction) {
		Log::add("\tDelete\t{$billing_transaction}\t{$billing_transaction->user}\t{$billing_transaction->account} ", 'switch_to_formal_run');
		$billing_transaction->delete();
		$i++;
	}
	Log::add("\t共删除{$i}条billing_transaction ", 'switch_to_formal_run');
}

/*
// 重新计算财务帐号的收入/余额/支出信息
// 其实该段代码不必要执行，但是由于cf库中的代码存在bug，故执行之。
Log::add("更新billing_account的总额、余额等信息: ", 'switch_to_formal_run');
$accounts = Q('billing_account');
if (count($accounts)) {
	$i = 0;
	$db = ORM_Model::db('billing_account');
	foreach ($accounts as $account) {
		Log::add("\tEdit\t{$account}\t{$account->amount}\t{$account->balance}", 'switch_to_formal_run');
		$account->amount = floatval($db->value('SELECT SUM(income) FROM billing_transaction WHERE account_id = %d', $account->id));
		$account->balance = floatval($db->value('SELECT balance FROM billing_transaction WHERE account_id=%d ORDER BY ctime DESC, id DESC LIMIT 1', $account->id));
		$account->save();
		Log::add("\t{$account->amount}\t{$account->balance} ", 'switch_to_formal_run');
		$i++;
	}
	Log::add("更新了{$i}条billing_account ", 'switch_to_formal_run');
}
 */
