#!/usr/bin/env php
<?php
//SITE_ID=XX LAB_ID=XX php env_abnormal_nodata_notification.php

require 'base.php';
$now = time();

$page = 50;
$num = 0;
$start = $num * $page;

$sensors = Q("env_sensor:limit({$start},{$page})");

while ($sensors->length() > 0) {
	//所有在监控的传感器
	foreach ($sensors as $sensor) {
		if ($sensor->status == Env_Sensor_Model::IN_SERVICE && $sensor->data_alarm) {
		 	
			$limit_nodata_times = $sensor->nodata_check_status ? $sensor->limit_nodata_times : Config::get('envmon.limit_nodata_times', 3);
			//检测间隔
			$check_nodata_time = ($sensor->nodata_check_status ? $sensor->check_nodata_time : Config::get('envmon.check_nodata_time', 5)) * 60;
			$nodata_alert_time = ($sensor->nodata_check_status ? $sensor->nodata_alert_time : Config::get('envmon.nodata_alert_time', 5)) * 60;

		 	$db = ORM_Model::db('env_datapoint');
			//count用来判断是否有数据
			$count = $db->value("SELECT COUNT(value) FROM env_datapoint WHERE ctime >= %d AND ctime <= %d AND sensor_id = %d", $now - $nodata_alert_time, $now, $sensor->id);
			$count = round($count, 1);
			//报警的次数
			$alert_times = (int)$sensor->_alert_time_nodata;
			$warning_time = (int)$sensor->_warning_nodata_time;
		
		
			if ($count == 0) {
				if (($now > $check_nodata_time + $sensor->ctime) && ($limit_nodata_times == 0 || $alert_times < $limit_nodata_times) && $now - $warning_time >= $check_nodata_time) {
				
					$nodata_dtstart = $now - $nodata_alert_time;
					$content = [
							'%node'=> $sensor->node->name,
				            '%sensor' => $sensor->name,
				            '%dtstart' => Date::format($nodata_dtstart),
				            '%dtend' => Date::relative($now, $nodata_dtstart),
						];
					send_notification('envmon.sensor.nodata', $sensor, $content);
				
					$sensor->_alert_time_nodata ++;
					$sensor->_warning_nodata_time = $now;
					$sensor->save();

					if ($sensor->_alert_time_nodata == 1) {
						$env_sensor_alarm = O('env_sensor_alarm');
						$env_sensor_alarm->sensor = $sensor;
						$env_sensor_alarm->dtstart = $nodata_dtstart;
						$env_sensor_alarm->save();
					}
				}
			}
			else {
				$env_sensor_alarm = Q("env_sensor_alarm[dtend=0][sensor={$sensor}]:sort(ctime D):limit(1)")->current();
				if ($env_sensor_alarm->id) {
					$env_sensor_alarm->dtend = $now;
					$env_sensor_alarm->save();
				}
				$sensor->_alert_time_nodata = 0;
				$sensor->save();
			}
		
		
			//数据异常的处理
			$limit_abnormal_times = $sensor->abnormal_check_status ? $sensor->limit_abnormal_times : Config::get('envmon.limit_abnormal_times', 3);
			//检测间隔
			$check_abnormal_time = ($sensor->abnormal_check_status ? $sensor->check_abnormal_time : Config::get('envmon.check_abnormal_time', 5)) * 60;
			$alert_time = ($sensor->abnormal_check_status ? $sensor->alert_time : Config::get('envmon.alert_time', 5)) * 60;
		
			
			//报警次数
			$alert_times = (int)$sensor->_alert_times_abnormal;
			//上次报警时间
			$warning_time = (int)$sensor->_warning_time;
		
		
			$db = ORM_Model::db('env_datapoint');
			$average = $db->value("SELECT AVG(value) FROM env_datapoint WHERE ctime >= %d AND ctime <= %d AND sensor_id = %d", $now - $alert_time, $now, $sensor->id);
			if (($average > $sensor->vto || $average < $sensor->vfrom) && $average !== null) {	
				//在检测间隔后，次数小于设置时产生报警
				if (($now > $check_abnormal_time + $sensor->ctime) && ($limit_abnormal_times == 0 || $alert_times < $limit_abnormal_times) && $now - $warning_time >= $check_abnormal_time) {
					//这次警报的时间
					$abnormal_dtstart = $now - $alert_time;
					$average = round($average, 1);
					$content = [
							'%node'=> $sensor->node->name,
				            '%sensor' => $sensor->name,
				            '%dtstart' => Date::format($abnormal_dtstart),
				            '%dtend' => Date::relative($now, $abnormal_dtstart),
				            '%data' => $average.$sensor->unit()
						];
					send_notification('envmon.sensor.warning', $sensor, $content);

					$sensor->_warning_time = $now;
					$sensor->_alert_times_abnormal++;
					$sensor->save();
					if ($sensor->_alert_times_abnormal == 1) {
						$env_sensor_alarm = O('env_sensor_alarm');
						$env_sensor_alarm->sensor = $sensor;
						$env_sensor_alarm->dtstart = $abnormal_dtstart;
						$env_sensor_alarm->save();
					}
				}
			}
			else {
				//如果数据正常了设置为0
				$env_sensor_alarm = Q("env_sensor_alarm[dtend=0][sensor={$sensor}]:sort(ctime D):limit(1)")->current();
				if($env_sensor_alarm->id) {
					$env_sensor_alarm->dtend = $now;
					$env_sensor_alarm->save();
				}
				$sensor->_alert_times_abnormal = 0;
				$sensor->save();
			
			}
		
		}
	}
	
	unset($sensors);
	$num++;
	$start = $num * $page;
	$sensors = Q("env_sensor:limit({$start},{$page})");
}

//发送消息
function send_notification($notify_key, $sensor, $content) {
	$node = $sensor->node;

	//监控对象负责人
	$send_users = Q("{$node} user.incharge")->to_assoc('id', 'id');

	//系统设置中需要发送的人
	$tokens = Config::get('envmon.admin');
	$tokens = is_array($tokens) ? $tokens : [$tokens];
	foreach ($tokens as $token) {
		$user = O('user', ['token' => Auth::normalize($token)]);
		if ($user->id) {
			$token_users[] = $user->id;
		}
	}

	$user_array = $send_users + $token_users;

	$u = implode(',', $user_array);
	$users = Q("user[id={$u}]");

    foreach ($users as $user) {
    	$content['%user'] = Markup::encode_Q($user);
        Notification::send($notify_key, $user, $content);
    }

}
	
	


