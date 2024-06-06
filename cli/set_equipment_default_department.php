#!/usr/bin/env php
<?php

require "base.php";


$department = Q('billing_department[name=default]:limit(1)')->current();

if (!$department->id) {
	$department = O('billing_department');
	$department->name = 'default';
	$department->save();
}

$equipments = Q('equipment');

foreach($equipments as $equipment) {
	if (!$equipment->billing_dept->id) {
		echo "更新{$equipment->name}[{$equipment->id}]\n";
		$equipment->billing_dept = $department;
   		$equipment->save();
	}
}


