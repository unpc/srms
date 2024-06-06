#!/usr/bin/env php
<?php

//hongjie.zhu

require "base.php";


try {

	$transactions = Q('billing_transaction[ctime<='.time().']');
	foreach($transactions as $transaction){
		$transaction->status = Fin_Transaction_Model::STATUS_CONFIRM;
		$transaction->save();
	}

}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

