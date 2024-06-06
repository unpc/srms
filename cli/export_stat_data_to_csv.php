#!/usr/bin/env php

<?php

  /*
   * @file      list_eq_stat_info_everyday.php
   * @author 	Rui Ma <rui.ma@geneegroup.com>
   * @date		2011.12.6
   *
   * @brief	    每天01:00执行该文件获取前一天的eq_stat的信息	
   * 
   * usage: SITE_LAB=cf LAB_ID=test ./list_eq_stat_info.php
   */

require 'base.php';

$now = time();

//获取前一天的起止时间
$dtstart = mktime(0, 0, 0, date('m', $now) - 1, date('d', $now) - 1);
$dtend = mktime(23, 59, 59, date('m', $now), date('d', $now));

//EQ_Stat_List::do_stat_list_save($dtstart, $dtend, TRUE);
EQ_Stat_List::export_csv(0, 0);
