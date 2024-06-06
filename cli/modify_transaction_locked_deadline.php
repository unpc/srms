#!/usr/bin/env php
<?php
require 'base.php';

Upgrader::echo_title(strtr('当前系统锁定时间为%time', ['%time'=>Date::format(Lab::get('transaction_locked_deadline'), 'Y/m/d')]));

Upgrader::echo_fail('警告：输入锁定时间后，该日期之前的仪器使用记录都无法被修改！');
fwrite(STDOUT, '请输入锁定时间xxxx-xx-xx：');

$deadline = strtotime(fgets(STDIN) ? : 0);

$deadline += 86399;

// 1. 重设 deadline
$deadline = Event::trigger('transaction_locked_deadline.modify', $deadline) ? : $deadline;
Lab::set('transaction_locked_deadline', $deadline);

// 2. 解锁已锁定的 fin_transaction ，锁定fin_transaction
$db = Database::factory();

if (Module::is_installed('billing')) {
	$lock_query = "UPDATE billing_transaction SET status = 1 WHERE ctime <= %d AND source='local'";
	$db->query($lock_query, $deadline);

	$unlock_query = "UPDATE billing_transaction SET status = 0 WHERE ctime > %d AND source='local'";
	$db->query($unlock_query, $deadline);

	//调整财务账号的扣费，所有锁定的充值才计入财务账号
	$start = 0;
	$per_page = 10;
	$query_sql = 'select * from billing_account limit %d, %d';

	while (TRUE) {
		$query = $db->query($query_sql, $start, $per_page);
		if (!$query || !count($rows = $query->rows())) break;

		foreach($rows as $a) {
			$amount = $db->value("SELECT SUM(income) FROM billing_transaction WHERE account_id = %d AND (status = %d or source='local')", $a->id, Billing_Transaction_Model::STATUS_CONFIRMED) ?: 0;
			$outcome = $db->value('SELECT SUM(outcome) FROM billing_transaction WHERE account_id = %d', $a->id) ?: 0;
			$balance = $amount - $outcome;
			$update_sql = "UPDATE billing_account SET balance='$balance' where id=$a->id";
			$db->query($update_sql);

		}
		$start += $per_page;
	}
}

// 3. 将所有锁定时间内的record的未反馈的记录进行反馈, 且将备注设置成为系统内置
$feedback_query = "UPDATE eq_record SET status = %d, feedback = '%s' WHERE status = %d AND dtend <= %d AND dtend != 0";
$no_status = EQ_Record_Model::FEEDBACK_NOTHING;
$normal_status = EQ_Record_Model::FEEDBACK_NORMAL;
$feedback = I18N::T('equipments', '系统锁定记录时自动对记录进行反馈!');

$db->query($feedback_query, $normal_status, $feedback, $no_status, $deadline);


Upgrader::echo_success(strtr('锁定时间修正为%time成功！', ['%time'=>Date::format($deadline, 'Y/m/d')]));
