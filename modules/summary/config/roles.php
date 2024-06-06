<?php

/**
 * 之前是由于通过perm找user的selector不会写，这里加了一个默认角色
 * 当前已可通过perm直接找到关联user，所以取消该默认角色
 */

// define('ROLE_REPORT_CHARGE', -50);
// $config['default_roles'][ROLE_REPORT_CHARGE] = ['key' => 'report_charge', 'name' => '科技部申报任务相关人员', 'weight' => ROLE_REPORT_CHARGE];
if (!$GLOBALS['preload']['gateway.perm_in_uno']) {
define('ROLE_NRII_HELP', -40);
$config['default_roles'][ROLE_NRII_HELP] = ['key' => 'nrii_help', 'name' => '数据上报-辅助填报人', 'weight' => ROLE_NRII_HELP];
}

