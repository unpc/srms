#!/usr/bin/env php
<?php

require "base.php";


try {

	Cache::L('ME', O('user', 1));
	EQ_Banned_Model::$disabled = TRUE;
	Q("eq_charge")->delete_all();
	Q("transaction[outcome>0]")->delete_all();
	foreach(Q("lab") as $lab) {
		$transaction = Q("transaction[lab=$lab]:sort(ctime A):limit(1)")->current();
		if ($transaction->id) {
			$transaction->save();
		}
	}
	// Q("eq_charge[dtstart>=1271812080][dtstart<=1272272213]")->delete_all();	
	foreach(Q("eq_record[dtstart>=1271806980]:sort(dtstart A)") as $record) {
		Cache::L('ME', $record->user);
		$record->dtstart = $record->dtstart;
		$record->dtend = $record->dtend;
		$record->save();
	}
	EQ_Banned_Model::$disabled = FALSE;
	
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}


