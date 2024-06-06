#!/usr/bin/env php
<?php
  /*
	此脚本用来处理 2011年6月 南开系统在一次lims logon 升级后，
	生成若干错误记录的问题。

	错误记录是指:
	1.结束时间在 6月1日 后；
	2.由机主、测试用户或技术支持使用；
	3.与相邻记录开始/结束时间紧贴（相同或相差一秒）;
	4.未设置过计费；(可被条件2覆盖)

	(xiaopei.li@2011.06.13)
   */
require "base.php";

$equipments = Q('equipment');

$n_eq = count($equipments);
echo "there're {$n_eq} equipments\n";

$test_user_id = [46, 1];

$n_merged = 0;

foreach ($equipments as $equipment) { /* 遍历所有仪器 */
	echo "正在处理 {$equipment->name}\n";
	
	$incharges = Q("{$equipment} user.incharge");

	$gangsters = $incharges->to_assoc('id', 'id');
	$gangsters = array_merge($gangsters, $test_user_id); /* 要检测的用户 */
	$gangsters = join(', ', $gangsters);

	$dtend = strtotime('20110601');
	
	$records = Q("({$equipment}) eq_record[user={$gangsters}][dtend>{$dtend}]:sort(dtstart D)");
	foreach ($records as $record) {

		printf("%s - %s  %s\n ", date('ymd h:m:s', $record->dtstart),
			   date('ymd h:m:s', $record->dtend), $record->user->name);
		
		if (!isset($prev_record)) {
			$prev_record = $record;
		}

		if ($record->id != $prev_record->id) {
			if ($prev_record->dtstart == $record->dtend ||
				$prev_record->dtstart == $record->dtend + 1 &&
				$prev_record->user->id == $record->user->id
				) { /* 如果符合"错误时间" */
				$prev_record->dtstart = $record->dtstart;
				$record->delete();
				$prev_record->save();
				$n_merged ++;
			}
			else {
				$prev_record = $record;
			}
		}
	}
}

echo "merged {$n_merged} records\n";
