<?php

class CLI_Billing_Transaction {

	static function correct_transaction() {
		$time = Config::get('billing.correct_time') ? : 0;
		$status = Billing_Transaction_Model::STATUS_CONFIRMED;
		foreach (Q('billing_account') as $account) {
			$selector = "billing_transaction[account=$account]";
			$account->income_remote = (double)Q($selector . "[income>0][source!=local]")->sum('income');
			$account->income_remote_confirmed = (double)Q($selector . "[income>0][source!=local][status=$status]")->sum('income');
			$account->income_local = (double)Q($selector. "[source=local][transfer=0]")->sum('income');
			$account->income_transfer = (double)Q($selector. "[income>0][transfer>0]")->sum('income');
			$account->outcome_remote = (double)Q($selector . "[outcome>0][source!=local][transfer=0]")->sum('outcome');
			$account->outcome_local = (double)Q($selector . "[outcome>0][source=local][transfer=0][manual>0]")->sum('outcome');
			$account->outcome_transfer = (double)Q($selector. "[outcome>0][source=local][transfer>0][manual>0]")->sum('outcome');
			$account->outcome_use = (double)Q($selector. "[outcome>0][source=local][transfer=0][manual=0]")->sum('outcome');

	        $db = Database::factory();
			$account->balance = (double) ($account->income_remote_confirmed + $account->income_local + $account->income_transfer - $account->outcome_remote - $account->outcome_local - $account->outcome_use - $account->outcome_transfer);

			$account->save();
		}
	}

	static function modify_locked_deadline() {
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
		$lock_query = "UPDATE billing_transaction SET status = 1 AND source='local' WHERE ctime <= %d";
		$db->query($lock_query, $deadline);

		$unlock_query = "UPDATE billing_transaction SET status = 0 AND source='local' WHERE ctime > %d";
		$db->query($unlock_query, $deadline);

		//调整财务账号的扣费，所有锁定的充值才计入财务账号
		$start = 0;
		$pre_page = 10;
		$query_sql = 'select * from billing_account limit %d, %d';

		while ($rows = $db->query($query_sql, $start, $pre_page)->rows()) {
			foreach($rows as $a) {
				$amount = $db->value("SELECT SUM(income) FROM billing_transaction WHERE account_id = %d AND (status = %d or source='local')", $a->id, Billing_Transaction_Model::STATUS_CONFIRMED) ?: 0;
				$outcome = $db->value('SELECT SUM(outcome) FROM billing_transaction WHERE account_id = %d', $a->id) ?: 0;
				$balance = $amount - $outcome;
				$update_sql = "UPDATE billing_account SET amount='$amount',balance='$balance' where id=$a->id";
				$db->query($update_sql);

			}
			$start += $pre_page;
		}

		// 3. 将所有锁定时间内的record的未反馈的记录进行反馈, 且将备注设置成为系统内置
		$feedback_query = "UPDATE eq_record SET status = %d, feedback = '%s' WHERE status = %d AND dtstart <= %d";
		$no_status = EQ_Record_Model::FEEDBACK_NOTHING;
		$normal_status = EQ_Record_Model::FEEDBACK_NORMAL;
		$feedback = "系统锁定记录时自动对记录进行反馈!";

		$db->query($feedback_query, $normal_status, $feedback, $no_status, $deadline);
		Upgrader::echo_success(strtr('锁定时间修正为%time成功！', ['%time'=>Date::format($deadline, 'Y/m/d')]));
	}

}
