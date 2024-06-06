<?php
class CLI_Vidmon {
	static function delete_overdue_capture_and_data() {
		$now = Date::time();

		//最大存储时间
		$capture_max_live_time = Config::get('vidmon.capture_max_live_time');

		//删除过期的不为alarm的vidcam_capture_data
		$ctime = $now - $capture_max_live_time;
		$db = Database::factory();

		while (count($rows = $db->query('SELECT id FROM vidcam_capture_data WHERE ctime < %d limit 100', $ctime)->rows())) {
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
	}
}
