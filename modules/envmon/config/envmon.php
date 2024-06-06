<?php

$config['unit'] = '°C';

$config['admin'] = ['genee'];


/*
一些配置, 参考以下邮件 (xiaopei.li@2012-07-16)
From: 	LiuCheng <cheng.liu@geneegroup.com>
Subject: 	Envmon中配置参数表
Date: 	July 16, 2012 1:14:39 PM GMT+08:00
To: 	Xiaopei Li <xiaopei.li@geneegroup.com>

// 传感器没有数据的报警间隔时间
$config['no_data_warning_interval']
// 每次查询的actual_data的时间段
$config['cache_time']
// 上次进行发送接受的时间间隔值
$config['min_active_interval']
// env_actual_datapoint 记录的缓冲时间
$config['cache_time']
// 821无数据再次查询的次数
$config['nodata_max_retries']
// 清空env_actual_datapoint数据的时间间隔
$config['clean_up_interval']
// 更新查询列表中之前无数据被清除掉的sensor间隔
$config['sensor_update_interval']
// 同一sensor需要两次查询的间隔时间值
$config['sample_interval']
// tszz超时等待的时间间隔
$config['timeout']


*/
$config['limit_nodata_times'] = 3;//无数据报警次数
$config['check_nodata_time'] = 5;//无数据检测数据间隔
$config['nodata_alert_time'] = 5;//检测多长时间的数据

$config['limit_abnormal_times'] = 3;//数据异常报警的次数
$config['check_abnormal_time'] = 5;// 数据异常检测的时间间隔
$config['alert_time'] = 5;//检测多长时间的数据




