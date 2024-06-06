#!/usr/bin/env php
<?php
/*
	结束已超时仪器的当前使用(xiaopei.li@2012-09-12)
*/

require 'base.php';

// 需要预约, 正在使用, 且可关闭的仪器
$equipments = Q("equipment[accept_reserv][is_using][is_monitoring]");

$now = time();
$reserve_type = Cal_Component_Model::TYPE_VEVENT;

foreach ($equipments as $eq) {
	// error_log($eq->name);
	if (Q("calendar[parent={$eq}] cal_component[type={$reserve_type}][dtstart~dtend={$now}]:limit(1)")->current()->id) {
		continue;
	}
	// ...不在预约时段(即已超时)

	$current_user = $eq->current_user();
	if (!$current_user->id) {
		continue;
	}
	// error_log($current_user);
	// error_log('======== ' . $eq->name . "\n");

	$admins = $eq->get_free_access_users(); // [id => user]

	$is_admin_using = FALSE;
	foreach (array_keys($admins) as $admin_id) {
		if ($current_user->id == $admin_id) {
			$is_admin_using = TRUE;
			break;
		}
	}

	if (!$is_admin_using) {
		// error_log('close');
		// continue;
		// ...使用者非仪器管理员

		// 关闭!
		$agent = new Device_Agent($eq);
		// error_log("======= shut down \n");
		$success = $agent->call('switch_to', ['power_on'=>FALSE]);

		if ($success) {
			// error_log( "====== done \n");
			$eq->auto_kicked_out = TRUE;
			$eq->is_using = FALSE;
			$eq->save();
			Log::add(sprintf('[equipments] %s[%d]仪器预约到时自动关闭', $eq->name, $eq->id), 'journal');
		}
		else {
			// error_log('fail');
		}
	}
}


$last_run = $now-60; // 该脚本每分钟运行, 所以正常情况下, 需要调整的记录范围是 "dtend 在上次运行到这次运行间"
foreach (Q("eq_record[dtend>$last_run][dtend<=$now]") as $record) {
	if ($record->reserv->id && $record->equipment->auto_kicked_out) {
		$record->dtend = $record->reserv->dtend;
		$record->not_overtime = TRUE;
		$record->save();

		$record->equipment->auto_kicked_out = NULL;
		$record->equipment->save();
	}
}
