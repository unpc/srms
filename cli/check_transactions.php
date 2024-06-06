#!/usr/bin/env php
<?php

require "base.php";


try {

	foreach (Q("lab") as $lab) {
		
		do {

			$balance = 0;
			$failed = FALSE;

			foreach (Q("$lab transaction:sort(ctime ASC)") as $transaction) {
				$expect_balance = $balance + $transaction->income - $transaction->outcome;
				if (round($transaction->balance, 2) != round($expect_balance, 2)) {
					printf("异常 [%d] %s 实验室%s[%d] 收入%0.2f 支出%0.2f 应结余%0.2f 实结余%0.2f %s\n",
						$transaction->id,
						Date::format($transaction->ctime),
						$lab->name, $lab->id,
						$transaction->income, $transaction->outcome,
						$expect_balance, $transaction->balance,
						(string) new Markup($transaction->description)
					);
					
					$transaction->update_subsequent();
					$failed = TRUE;
					break;
				}
				$balance = $transaction->balance;
			}
			
			
		}
		while ($failed);
		
		$transaction = Q("$lab transaction:limit(1)")->current();
		if ($transaction->id) {
			$transaction->update_lab_balance();
		}
		
	}

		
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

