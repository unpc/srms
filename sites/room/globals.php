<?php

//【允许/不允许】多级管理
$GLOBALS['preload']['roles.enable_subgroup_access'] = TRUE;

//允许重复事件
$GLOBALS['preload']['calendars.enable_repeat_event'] = TRUE;

//仪器公告开关
$GLOBALS['preload']['equipment.enable_announcement'] = TRUE;

//单一仪器黑名单开关
$GLOBALS['preload']['equipment.enable_specific_blacklist'] = TRUE;

$GLOBALS['preload']['tag.group_limit'] = 0;

$GLOBALS['preload']['people.enable_member_date'] = TRUE;

$GLOBALS['preload']['lab.max_members'] = 99999;

$GLOBALS['preload']['lab.max_active_members'] = 50000;

// $GLOBALS['preload']['people.multi_lab'] = true;

/*
  TODO 客户端数量和仪器相关限制的 GLOBALS 参数形式应进一步设计, 做到通用/清晰(xiaopei.li@2012-02-10)
 */

/*
  // 客户端数量(xiaopei.li@2011-12-28)
  $GLOBALS['preload']['clients']['computer']['port'] = 2430;
  $GLOBALS['preload']['clients']['computer']['max'] = 40;
*/

/*
  // 仪器相关限制示例(xiaopei.li@2012-02-10)
  $GLOBALS['preload']['equipment.max_number'] = 100; // 总仪器台数
  $GLOBALS['preload']['equipment.max_clients'] = 80;	// 可安客户端数
  $GLOBALS['preload']['equipment.max_power_clients'] = 40; // 可安电源客户端数
  $GLOBALS['preload']['equipment.max_computer_clients'] = 40; // 可安电脑客户端数
*/
