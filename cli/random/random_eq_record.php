#!/usr/bin/env php
<?php

require "base.php";

class Record_Gen {

	static function generate($dtmin, $dtmax, $dur) {
		static $uid_max, $eid_max;
		if (!$uid_max) $uid_max = (int) Q("user:sort(id D):limit(1)")->current()->id;
		if (!$eid_max) $eid_max = (int) Q("equipment:sort(id D):limit(1)")->current()->id;

		$user = self::random_object('user', 1, $uid_max);
		$equipment = self::random_object('equipment', 1, $eid_max);

		Cache::L('ME', $user);
		$record = O('eq_record');
		$record->equipment = $equipment;
		$record->user = $user;
		$record->status =  EQ_Record_Model::FEEDBACK_NORMAL;

		$tries = 0;
		do {
			$record->dtstart = $dtstart = rand($dtmin, $dtmax);
			$record->dtend = $dtend = $dtstart + $dur;
			if (Q("eq_record[user=$user][equipment=$equipment][dtstart~dtend=$dtstart|dtstart~dtend=$dtend|dtstart=$dtstart~$dtend]")->length() == 0) {
				$record->save();
				echo "{$user->name} 使用 {$equipment->name} 时间:".Date::range($dtstart, $dtend)."\n";
				break;
			}
			$tries ++;
		}
		while ($tries < 5);

	}

	static function random_object($oname, $id_min, $id_max) {
		
		while (!$object->id) {
			$id = rand($id_min, $id_max);
			$object = O($oname, $id);
		}

		return $object;
	}

}


$dtstart = strtotime('2009-1-1 13:00');
$dtend = strtotime('2010-12-25 13:00');
$dur_min = 60 * 15;	//15 min
$dur_max = 60 * 60 * 3; //3 hours

$max = (int)$argv[1];
for($i=0; $i<$max; $i++) {
	Record_Gen::generate($dtstart, $dtend, rand($dur_min, $dur_max));
}
