#!/usr/bin/env php
<?php
include '../base.php';

/* backup */
// 数据库备份文件名
$time = date('Ymdhis', time());
$dbfile = LAB_PATH . 'private/backup/fix_fin_transactions'.$time.'.sql';

File::check_path($dbfile);
$db = Database::factory();
$db->snapshot($dbfile);

/* check */
$accts = Q('fin_account');

$output_file = tempnam('/tmp', 'error_fin_trans');
$output = new CSV($output_file, 'w');
$output->write([
				   'id',
				   'ctime',
				   'account',
				   'balance',
				   'balance_should',
				   ]);
$n_errors = 0;

foreach ($accts as $acct) {
	$transactions = Q("$acct<account fin_transaction:sort(ctime A, id A)");

	if ($transactions->total_count() > 0) {
		$balance_before = 0;
		$first_transaction = $transactions->current();
		foreach ($transactions as $t) {
			$balance_should  = $balance_before + $t->income - $t->outcome;

			if (floatval($balance_should) - floatval($t->balance) > 0.01) {
				// 如果有错误记录
				// 就重新保存第一条记录，以更新所有记录

				/*
				Upgrader::echo_fail("发生于: " . Date::format($t->ctime) . '   '  .
									$balance_should . '!=' . $t->balance .
									' result: ' . (floatval($balance_should) - floatval($t->balance)) );
				*/

				$output->write([
								   $t->id,
								   $t->ctime,
								   $t->account->id,
								   $t->balance,
								   $balance_should,
								   ]);
				$n_errors++;

				$first_transaction->income = $first_transaction->income;
				$first_transaction->outcome = $first_transaction->outcome;

				if ($first_transaction->save()) {
					break;
				}
			}
			$balance_before = $t->balance;
		}
	}
}

$output->write(['备份文件', $dbfile]);

$output->close();

if ($n_errors) {
	error_log('has error');
	error_log('see detail info at: ' . $output_file);

	$email = new Email;
	$receiver ='support@geneegroup.com';
	$email->to($receiver);
	$email->subject('error_fin_transactions_' . date('Ymd', time()));
	$email->body(file_get_contents($output_file));
	if ($email->send()) {
		Log::add(T("发现错误transaction数据，并已邮件已发送给 %receiver", [
					   '%receiver' => $receiver
					   ]), 'journal');
	}
}
else {
	@unlink($dbfile);
	// 若无错, 删除备份(xiaopei.li@2011.11.17)
}

// unlink($output_file);
