#!/usr/bin/env php
<?php
/*

yu.li
SITE_ID=xx LAB_ID=xx php 11-fix_miss_reserv.php 
*/
$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;


$u = new Upgrader;

$u->check = function() {
	return TRUE;
};

//数据库备份
$u->backup = function() {
	$dbfile = LAB_PATH . 'private/backup/before_fix_miss_reserv.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '备份数据库表');
	$db = Database::factory();
	return $db->snapshot($dbfile);
};

$u->upgrade = function() {

    $db = Database::factory();

	$start=strtotime('2013-10-23 12:00:00');
	$end=strtotime('2013-10-25 5:00:00');

	$equipments = Q('equipment');
	foreach($equipments as $equipment){
		echo '.';
		if(!$equipment->control_mode){
			$sql = "update `eq_reserv` set `status`=1 where `equipment_id`={$equipment->id} and `status`=2 and dtstart>{$start} and dtend<{$end}";
			$db->query($sql);
			$affecteds = 0;
			$affecteds = $db->affected_rows();
			if($affecteds){
				echo "\n";
				echo "更新了不控制仪器: $equipment\n";
				echo "修改了 $equipment 的预约: $affecteds 个";
				echo "\n";
			}
			
		}
		else{
			$reserv_count = 0;
			$reservs = Q("eq_reserv[status=2][dtstart>{$start}][dtstart<{$end}][equipment={$equipment}]");
			foreach($reservs as $reserv){
				$using_records = Q("eq_record[dtend=0][dtstart={$reserv->dtstart}~{$reserv->dtend}]");
				if($using_records->total_count()){
					$reserv->status = 0;
					$reserv->save();
					$reserv_count++;
					continue;
				}

				$records = Q("eq_record[reserv={$reserv}]");
				if($records->total_count()){
					$reserv->status = 1;
					$reserv->save();
					$reserv_count++;
					continue;
				}
			}

			if($reserv_count){
				echo "\n";
				echo "更新了仪器: $equipment\n";
				echo "修改了 $equipment 的预约: $reserv_count 个";
				echo "\n";
			}
		}
	}
};

//恢复数据
$u->restore = function() {
	$dbfile = LAB_PATH . 'private/backup/before_fix_miss_reserv.sql';
	File::check_path($dbfile);
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, '恢复数据库表');
	$db = Database::factory();
	$db->restore($dbfile);
};

$u->run();
