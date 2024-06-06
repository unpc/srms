<?php
// 查找与指定 eq_record 关联的 eq_charge
require 'base.php';
$record_ids = [
			// 123, 234,
        ];
foreach ($record_ids as $rid) {
	$record = O('eq_record', $rid);
	$charge = Q("eq_charge[equipment=$record->equipment][user=$record->user][dtstart~dtend=$record->dtstart|dtstart~dtend=$record->dtend|dtstart=$record->dtstart~$record->dtend]:sort(dtstart A):limit(1)")->current();
	echo $record . ' => ' . $charge . "\n";
}

