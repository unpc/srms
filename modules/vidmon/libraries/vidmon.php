<?php

class Vidmon {

	static function vidcam_ACL($e, $user, $perms, $vidmon, $options) {

        switch ($perms) {

            case '列表' :
                if ($user->access('查看视频监控模块') || Q("{$user}<incharge vidcam")->total_count()) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                break;
            case '修改' :
                if ($vidmon->id && self::user_is_vidcam_incharge($user, $vidmon)) {
                    $e->return_value = TRUE;
                }
            case '添加' :
            case '删除' :
                if ($user->access('管理视频设备')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                break;
            case '查看' :
                // 机主的负责仪器关联了摄像头，他即使没有监控视频设备权限，也能查看
                if ($user->access('监控视频设备') || 
                    ($vidmon->id && self::user_is_vidcam_incharge($user, $vidmon)) || 
                    Q("{$user}<incharge equipment<camera {$vidmon}")->total_count()) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '多屏监控':
                //如果用户是某个视频管理员, 也可进行多屏监控
                if ($user->access('监控视频设备') || Q("{$user}<incharge vidcam")->total_count()) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '查看历史记录':
                // 机主的负责仪器关联了摄像头，他即使没有监控视频设备权限，也能查看历史记录
                if ($user->access('监控视频设备') || 
                    ($vidmon->id && self::user_is_vidcam_incharge($user, $vidmon)) ||
                    Q("{$user}<incharge equipment<camera {$vidmon}")->total_count()) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            default :
                return FALSE;
        }
	}

    static function user_is_vidcam_incharge($user, $vidcam) {
        if ($user->id && $vidcam->id && Q("{$vidcam} user[id=$user->id].incharge")->total_count()) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

	static function is_accessible($e, $name) {

		$me = L('ME');
		if (!$me->is_allowed_to('列表', 'vidcam')) {
			$e->return_value = $is_accessible;
			return FALSE;
		}		
	}
	
	static function private_file($file) {
		return MODULE_PATH . 'vidmon/' . PRIVATE_BASE . 'vidmon/' . $file;
	}	

    static function video_file($vidcam) {
        $dir = Config::get('system.tmp_dir').'vidmon/';
        return $dir . $vidcam->id . '.jpg';
    }

    //获取存储alarm的文件路径
    static function video_capture_file($vidcam, $time) {
        
        $file_path = Config::get('vidmon.capture_path');

        is_numeric($vidcam) ? $file_path = $file_path.$vidcam.'/' : $file_path = $file_path.$vidcam->id.'/';

        $file_path = $file_path.date('Ymd', $time).'/'. $time. '.jpg';
        if (file::check_path($file_path)) {
            return $file_path;
        }
    }

    //获取存储alarm点的图片缩略图文件路径
    static function video_alarm_thumbnail_file($vidcam, $time) {

        $time = $time ? : Date::time();
        $file_path = Config::get('vidmon.capture_path');

        is_numeric($vidcam) ? $file_path = $file_path.$vidcam.'/' : $file_path = $file_path.$vidcam->id.'/';
        $file_path = $file_path.'thumbnail/'.date('Ymd', $time).'/'.$time.'.jpg';
        if (file::check_path($file_path)) {
            return $file_path;
        }
    }

    static function snapshot_refresh($ids) {
        $me = L('ME');
        foreach ((array)$ids as $id) {
            $vidcam = O('vidcam', $id);
            if ($vidcam->id && $me->is_allowed_to('查看', $vidcam)) {

                // 此处不更新key了 防止lims、vidmon出现不一致的情况，更换key应该从vidmon: keep_alive 主动发起
				// $now = Date::time();
				// if (!$vidcam->capture_key || $vidcam->capture_key_mtime + 30 < $now) {
				// 	$vidcam->capture_key = Misc::random_password(12);
				// 	$vidcam->capture_key_mtime = $now;
				// 	$vidcam->save();
				// }
                $client = new Vidmon_Client($vidcam);

                $upload_url = strtr(Config::get('vidmon.capture_upload_url'), [
                    '%vidcam_id'=>$vidcam->id
                ]);
                if (!$upload_url) {
                    $upload_url = $vidcam->url(NULL, NULL, NULL, 'snapshot_upload');
                }

                $last_call_time = $vidcam->last_call_time;

                if (Date::time() - $last_call_time > 30) {
                    try {
                        $client->online_capture([
                            'url' => $upload_url,
                            'address' => $vidcam->control_address,
                            'online_capture_duration' => Config::get('vidmon.online_capture_duration'),
                            'online_capture_timeout' => Config::get('vidmon.online_capture_timeout'),
                        ]);
                        $vidcam->last_call_time = Date::time();
                    }
                    catch(Exception $e) {
                    }
                }
            }
        }
    }

    static function stop_snapshot_agent($e, $vidcam, $old_data, $new_data) {
        if ($vidcam->id && $new_data['control_address'] != $old_data['control_address']) {
            $agent = new Device_Agent($vidcam);
            $agent->call('halt');
            unset($agent);
        }
    }

    static function get_images_array ($images ,$turn = '') {
        $images_array = [];
        foreach ($images as $image) {
            $images_array['name'][] = $image->ctime;
            $images_array['img'][] = $image->get_thumbnail('dialog');
        }
        if ($turn && $images->length() > 0) {
            $images_array['name'] = array_reverse($images_array['name']);
            $images_array['img'] = array_reverse($images_array['img']);
        }
        return $images_array;
    }
    
    static function delete_capture_data($vidcam, $dto) {
    	$db = Database::factory();
    	while (count($rows = $db->query('SELECT id FROM vidcam_capture_data WHERE vidcam_id = %d AND is_alarm = 0 AND ctime < %d limit 100', $vidcam->id, $dto)->rows())) {
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

    static function history_capture($vid, $time) {
        $vidcam = O('vidcam', $vid);

        $capture_file = Vidmon::video_capture_file($vidcam, $time);
             
        if (file_exists($capture_file)) return;
        
        $url = strtr(Config::get('vidmon.history_capture_url'), [
            '%vidcam_id'=>$vidcam->id
        ]);
        if (!$url) {
            $url = $vidcam->url(NULL, NULL, NULL, 'snapshot_history');
        }

        $client = new Vidmon_Client($vidcam);
        $ret = $client->history_capture([
            'time' => $time,
            'address' => $vidcam->control_address,
            'file' => $url,
            'key' => $vidcam->capture_key,
        ]);
    }
}
