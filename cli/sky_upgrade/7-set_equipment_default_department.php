#!/usr/bin/env php
<?php

// 仪器关联财务中心

require "../base.php";

$department = Q('billing_department')->current();


if (!$department->id) {
	echo 'no department!';
	exit(1);
}

$equipments = Q('equipment');

foreach($equipments as $equipment) {
	if (!$equipment->billing_dept->id) {
		echo "更新{$equipment->name}[{$equipment->id}]\n";
		$equipment->billing_dept = $department;
	
		if ($equipment->save()) {
			echo "saved\n";
		}
		else {
			echo "fail\n";
		}
	}
}
