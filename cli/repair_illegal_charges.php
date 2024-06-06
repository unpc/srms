#!/usr/bin/env php
<?php

require "base.php";


try {

	foreach (Q("eq_charge") as $charge) {
		$equipment = $charge->equipment;
		$user = $charge->user;
		$dtstart = $charge->dtstart;
		$dtend = $charge->dtend;
		
		$records = Q("eq_record[equipment=$equipment][user=$user][dtstart>=$dtstart][dtend<=$dtend|dtend=0]");
		if ($records->length() == 0) {
			$charge->delete();
			printf("%s[%d] user:%s[%d] %s...已删除\n", $equipment->name, $equipment->id,
				$user->name, $user->id,
				Date::range($dtstart, $dtend)
			);
		}
		
	}

		
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}

