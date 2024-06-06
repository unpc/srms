#!/usr/bin/env php
<?php

require "base.php";
//计算一段时间内仪器使用记录和使用预约的收费总和

fwrite(STDOUT, '请输仪器id: ');
$eq_id = fgets(STDIN) ? : 0;

$equipment = O('equipment', $eq_id);

if(!$equipment->id){
	echo $equipment;
	Upgrader::echo_fail('仪器id输入错误! ');
	return;
}

fwrite(STDOUT, '请输入开始时间xxxx-xx-xx: ');
$dtstart = strtotime(fgets(STDIN) ? : 0);

fwrite(STDOUT, '请输入结束时间xxxx-xx-xx: ');
$dtend = strtotime(fgets(STDIN) ? : 0);
$dtend += 86399;

$total_charge = 0;

$records = Q("eq_record[equipment={$equipment}][dtstart>={$dtstart}][dtstart<={$dtend}][dtend>0]");
foreach ($records as $record) {
	$record_charge = O('eq_charge', ['source'=>$record]);
	if($record_charge->id) $total_charge += $record_charge->amount;
}

$reservs = Q("eq_reserv[equipment={$equipment}][dtstart>={$dtstart}][dtstart<={$dtend}]");
foreach ($reservs as $reserv) {
	$reserv_charge = O('eq_charge', ['source'=>$reserv]);
	if($reserv_charge->id) $total_charge += $reserv_charge->amount;
}

Upgrader::echo_success("仪器 $equipment->name 再 ".Date::format($dtstart) .' - '.Date::format($dtend)."的使用收费为 : {$total_charge}");

