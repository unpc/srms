#!/usr/bin/env php
<?php
require "base.php";

fwrite(STDOUT, '请输入开始时间xxxx-xx-xx：');

$dtstart = fgets(STDIN);

fwrite(STDOUT, '请输入结束时间xxxx-xx-xx：');

$dtend = fgets(STDIN);

$dtstart = strtotime($dtstart ?: 0);
$dtend = strtotime($dtend);

$tmp = $dtstart;
while ($tmp <= $dtend) {
	$date = Date::format($tmp, 'Y-m-d');
	echo "正在同步更新 $date 数据....       ";
	EQ_Stat::do_stat_list_save($tmp, $tmp + 3600*24 - 1);
	Upgrader::echo_success(sprintf("数据同步更新成功!"));
	$tmp += 3600*24;
}

Upgrader::echo_success("数据更新完毕！^-^");


