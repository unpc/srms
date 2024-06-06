#!/usr/bin/env php
<?php

/*
	定时5分钟查找数据库中的数据，如若不存在数据，则可以认为该sensor处于故障状态
	SITE_ID=XX LAB_ID=XX php check_envmon_data.php $channel $addr
*/

require 'base.php';

/* 报警间隔的时间段 */
$interval = Config::get('envmon.no_data_warning_interval', 300);

//可以设定需要定时扫描的频道号
$channel = $argv[1] ?: Config::get('envmon.channel');

//可以设定需要定时扫描的821的地址，如若不设置则为空
$addr = $argv[2] ?: '';
	
$prefix = "tszz://{$channel}/{$addr}";

$tokens = Config::get('envmon.free_token');

/* 每次查询的actual_data的时间段 */
$actual_data_cache_time = Config::get('envmon.cache_time', 300);

$db = ORM_Model::db('env_sensor');
$query = $db->query("SELECT * FROM env_sensor WHERE address LIKE '%s%%'", $prefix);

if ($query) while ($sensor = $query->row()) {

    $_warning_time = (array)Lab::get('envmon.no_data_warning_time', []);

    $warning_time = $_warning_time[$sensor->id];

    $now = Date::time(); 
	
	$cache_time = $interval;

    if ((!$warning_time || $now - $warning_time > $cache_time) && $sensor->status == Env_Sensor_Model::IN_SERVICE) {
        $past = $now - $actual_data_cache_time;     
        
        $actual_db = ORM_Model::db('env_actual_datapoint');
        $count = $actual_db->value("SELECT COUNT(*) FROM env_actual_datapoint WHERE ctime >= %d AND ctime <= %d AND sensor_id = %d", $past, $now, $sensor->id);
        
        if (!$count) {
            $sensor = O('env_sensor', $sensor->id);
            $_warning_time[$sensor->id] = $now;

            Lab::set('envmon.no_data_warning_time', $_warning_time);

            $node = $sensor->node;
            foreach (Q("{$node} user") as $user) {
                Notification::send('envmon.sensor.nodata', $user, [
                    '%user' => Markup::encode_Q($user),
                    '%sensor' => $sensor->name,
                    '%dtstart' => Date::format($past),
                    '%node'=>$sensor->node->name,
                    '%dtend' => Date::relative($now, $past)
                ]);
            }

            $tokens = Config::get('envmon.admin');
            $tokens = is_array($tokens) ? $tokens : [$tokens];
            foreach ($tokens as $token) {
                $user = O('user', ['token' => Auth::normalize($token)]);
                if ($user->id) {
                    Notification::send('envmon.sensor.nodata', $user, [
                        '%user' => Markup::encode_Q($user),
                        '%sensor' => $sensor->name,
                        '%dtstart' => Date::format($past),
                        '%node'=>$sensor->node->name,
                        '%dtend' => Date::relative($now, $past)
                    ]);
                }
            }
        }
    }
	
}
