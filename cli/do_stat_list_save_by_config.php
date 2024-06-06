#!/usr/bin/env php
<?php

/*
 * @file      do_stat_list_save_by_config.php 
 * @author 	Rui Ma <rui.ma@geneegroup.com>
 * @date		2012.07.10
 *
 * @brief	    执行该文件后根据config中配置信息，进行仪器统计	
 * 
 * usage: SITE_LAB=cf LAB_ID=test ./do_stat_list_save_by_config.php
 */

require 'base.php';

$times = Config::get('eq_stat.lock.time', ['value'=>4, 'format'=>'m']);
$interval = Date::convert_interval($times['value'], $times['format']);


$now = getdate(time());
$dtend = mktime(0, 0, 0, $now['mon'], $now['mday'], $now['year']) - 1;
$dtstart = $dtend - $interval + 1;

$last_stat = Q("eq_stat:sort(time D)")->limit(1)->current();
$stat_start  = $last_stat->time;

$dtstart  = ($stat_start>0 && $stat_start < $dtstart) ? $stat_start : $dtstart;

$tmp = $dtstart;
while ($tmp <= $dtend) {
    $date = Date::format($tmp, 'Y-m-d');
    //error_log("正在同步更新 $date 数据....");
    EQ_Stat::do_stat_list_save($tmp, $tmp + 3600 * 24 - 1);
    $tmp += 3600 * 24;
}
