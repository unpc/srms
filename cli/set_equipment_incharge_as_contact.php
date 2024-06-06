#!/usr/bin/env php
<?php
require 'base.php';
$equipments = Q('equipment');
foreach ($equipments as $equipment) {
	$incharge = Q("$equipment user.incharge:limit(1)")->current();
	if ($incharge->id) {
		echo "指定仪器的联系人为{$incharge->name}\n";
		$equipment->contact = $incharge;
		$incharge->follow($equipment);
		$equipment->save();
	}
}
