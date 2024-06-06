#!/usr/bin/env php
<?php
require "base.php";

$args = $argv;
$start = $args[1] ?? 0;
$end = $args[2] ?? time();

$output = new CSV('abnormal.csv', 'w');
$output->write(
	[
		'使用记录ID',
		'用户ID',
		'用户姓名',
		'仪器ID',
		'仪器名称',
		'使用开始时间',
        '使用结束时间',
        '使用时长'
	]
);

$sql = "SELECT 
`record`.`id` AS `record_id`, `u`.`id` AS `user_id`, `u`.`name` AS `user_name`, `e`.`id` AS `equipment_id`, `e`.`name` AS `equipment_name`, 
FROM_UNIXTIME(`dtstart`) AS `dtstart`, FROM_UNIXTIME(`dtend`) AS `dtend`, `dtend` - `dtstart` AS `time`
FROM `eq_record` AS `record`
JOIN `equipment` AS `e` ON `record`.`equipment_id` = `e`.`id`
JOIN `_r_user_equipment` AS `r` ON `r`.`id2` = `e`.`id` AND `record`.`user_id` = `r`.`id1` AND `r`.`type` = 'incharge'
JOIN `user` AS `u` ON `record`.`user_id` = `u`.`id`
WHERE `dtend` > 0 
AND `dtend` - `dtstart` > 604800
AND `dtend` BETWEEN %d AND %d";

$db = Database::factory();
$query = $db->query($sql, $start, $end);
if (!$query || !count($rows = $query->rows())) return;

foreach($rows as $row) {
    $output->write([
        $row->record_id,
        $row->user_id,
        $row->user_name,
        $row->equipment_id,
        $row->equipment_name,
        $row->dtstart,
        $row->dtend,
        round($row->time / 3600, 2),
    ]);
}

$output->close();
