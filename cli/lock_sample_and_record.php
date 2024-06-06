#!/usr/bin/env php
<?php
require "base.php";

fwrite(STDOUT, '请输入需要锁定的开始时间xxxx-xx-xx：');

$time = fgets(STDIN);

$dtstart = strtotime($time);

fwrite(STDOUT, '请输入需要锁定的结束时间xxxx-xx-xx：');

$time = fgets(STDIN);

$dtend = strtotime($time."+1 day") - 1;

$date = Date::format($dtstart) . ' - ' .Date::format($dtend);

$db = Database::factory();

/*
Upgrader::echo_fail('=====================');
Upgrader::echo_fail('开始尝试锁定使用记录数据');
Upgrader::echo_fail('=====================');

$record_query = "UPDATE eq_record SET is_locked = 1 WHERE dtstart >= %d AND dtstart <= %d";

$ret = (int)$db->query($record_query, $dtstart, $dtend);

echo "\n";
if ($ret) {
	Upgrader::echo_success("<======成功更新了 $date 之间使用记录数据！======>");
}
else {
	Upgrader::echo_fail("<======$date 之间使用记录数据更新失败！======>");
}

echo "\n\n\n";
Upgrader::echo_fail('=====================');
Upgrader::echo_fail('开始尝试锁定送样数据');
Upgrader::echo_fail('=====================');

$sample_query = "UPDATE eq_sample SET is_locked = 1 WHERE dtstart >= %d AND dtstart <= %d";

$ret = $db->query($sample_query, $dtstart, $dtend);

echo "\n";
if ($ret) {
	Upgrader::echo_success("<======成功更新了 $date 之间使用送样数据！======>");
}
else {
	Upgrader::echo_fail("<======$date 之间送样数据更新失败！======>");
}

echo "\n\n\n";
Upgrader::echo_fail('=====================');
Upgrader::echo_fail('开始尝试锁定收费数据');
Upgrader::echo_fail('=====================');

$charge_query = "UPDATE eq_charge SET is_locked = 1 WHERE dtstart >= %d AND dtstart <= %d";

$ret = $db->query($charge_query, $dtstart, $dtend);

echo "\n";
if ($ret) {
	Upgrader::echo_success("<======成功更新了 $date 之间使用收费数据！======>");
}
else {
	Upgrader::echo_fail("<======$date 之间收费数据更新失败！======>");
}

*/
echo "\n\n\n";
Upgrader::echo_fail('=====================');
Upgrader::echo_fail('开始尝试财务明细数据');
Upgrader::echo_fail('=====================');

$tran_query = "UPDATE billing_transaction SET status = 1 WHERE ctime >= %d AND ctime <= %d";

$ret = $db->query($tran_query, $dtstart, $dtend);

echo "\n";
if ($ret) {
	Upgrader::echo_success("<======成功更新了 $date 之间财务明细数据！======>");
}
else {
	Upgrader::echo_fail("<======$date 之间财务明细数据更新失败！======>");
}

echo "\n\n\n";
Upgrader::echo_fail('开始尝试同步遗漏数据');
$samples = Q("billing_transaction[ctime>=$dtstart][ctime<=$dtend]<transaction eq_sample[dtstart<$dtstart | dtstart>$dtend][!is_locked]");

foreach ($samples as $sample) {
	$sample->is_lock = 1;
	if ($sample->save()) {
		Upgrader::echo_success("<======成功更新了 {$sample->name()} [$sample->id] 数据！======>");
	}
	else {
		Upgrader::echo_fail("<======{$sample->name()} [$sample->id] fail ======>");
	}
}

$charges = Q("billing_transaction[ctime>=$dtstart][ctime<=$dtend]<transaction eq_charge[dtstart<$dtstart | dtstart>$dtend][!is_locked]");

foreach ($charges as $charge) {
	$equipment = $charge->equipment;
	$user = $charge->user;
	$dtstart = $charge->dtstart;
	
	$record = Q("eq_record[equipment=$equipment][user=$user][dtstart~dtend=$dtstart]:limit(1)")->current();
	if ($record->id) {
		$record->is_locked = 1;
		if ($record->save()) {
			Upgrader::echo_success("<======成功更新了 {$record->name()} [$record->id] 数据！======>");
		}
		else {
			Upgrader::echo_fail("<======{$record->name()} [$record->id] fail ======>");
		}
	}
	
}





