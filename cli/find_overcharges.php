#!/usr/bin/env php
<?php

require "base.php";


try {

	foreach (Q("eq_charge") as $charge) {
		$equipment = $charge->equipment;
		$user = $charge->user;
		$dtstart = $charge->dtstart;
		$dtend = $charge->dtend;
		$id = $charge->id;
		
		if ($equipment->charge_mode != EQ_Charge::CHARGE_MODE_DURATION) continue;
		
		$charges = Q("eq_charge[id!=$id][equipment=$equipment][dtstart~dtend=$dtstart|dtstart~dtend=$dtend|dtstart=$dtstart~$dtend]");
		if ($charges->length() > 0) {
			printf("%s[%d] user:%s[%d] %s %s \n", $equipment->name, $equipment->id,
				$user->name, $user->id,
				Date::range($dtstart, $dtend), Number::currency($charge->amount)
			);
		}
		
	}

		
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

