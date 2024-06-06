#!/usr/bin/env php
<?php
    /*
     * file delete_overdue_capture_and_data.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2012-09-22
     *
     * useage SITE_ID=cf LAB_ID=jiangnan ./delete_overdue_capture.php
     * brief 删除过期的capture图片、vidcam_capture_data
     */

require 'base.php';

$now = Date::time();

//最大存储时间
$capture_max_live_time = Config::get('vidmon.capture_max_live_time');

//最后一次清空过期capture的时间
$last_clean_overdue_capture_time = Lab::get('last_clean_overdue_capture_time');

//删除过期的不为alarm的vidcam_capture_data
$ctime = $now - $capture_max_live_time;
$db = Database::factory();
while (count($rows = $db->query('SELECT id FROM vidcam_capture_data WHERE is_alarm=0 AND ctime < %d AND ctime > %d limit 100', $ctime, $last_clean_overdue_capture_time)->rows())) {

    foreach($rows as $row) {
        //删除vidcam_capture_data等相关信息
        $id = $row->id;
        $datas = $db->query('SELECT * FROM vidcam_capture_data WHERE ID = %d', $id)->row();
        $vidcam_id = $datas->vidcam_id;
        $time = $datas->ctime;

        $file = Vidmon::video_capture_file($vidcam_id, $time);
        $thumbnail_file = Vidmon::video_alarm_thumbnail_file($vidcam_id, $time);

        //删除vidcam_capture_data
        $db->query('DELETE FROM vidcam_capture_data WHERE ID = %d', $id);

        //删除文件
        File::delete($file);

        //删除缩略图
        File::delete($thumbnail_file);
    }
}

//设定最后一次清除过期capture的时间，由于可能修改capture_max_live_time，可能导致部分文件重复进行处理，故不存储本次操作的执行时间，而存储处理的文件时间范围时间
Lab::set('last_clean_overdue_capture_time', $now - $capture_max_live_time);
