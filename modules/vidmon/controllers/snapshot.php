<?php

class Snapshot_Controller extends Controller {

	function index($id=0) {

		$vidcam = O('vidcam', $id);

        //直接跳转
        if (!$vidcam->id) URI::redirect('/');

		$file = Vidmon::video_file($vidcam);
		File::check_path($file);

		$me = L('ME');
		$has_access = $me->is_allowed_to('查看', $vidcam);
        $capture_duration = Config::get('vidmon.capture_duration');
		if ($has_access && file_exists($file) && time() - filemtime($file) < $capture_duration * 2) {
			/*
			$im = ImageCreateFromJpeg($file);
			$bg = imagecolorallocate($im, 255, 255, 255);
			$black = imagecolorallocate($im, 0, 0, 0);
			$str = (string)Date::format(filemtime($file), 'Y-m-d H:i:s');
            imagestring($im, 5, 26, 26, $str, $black);
            imagestring($im, 5, 25, 25, $str, $bg);
            */
			header('Content-Type: image/jpeg');
			@readfile($file);
		}
		else {
			$file = Core::file_exists(PUBLIC_BASE.'images/capture_blank.gif', 'vidmon');
			header('Content-Type: image/gif');
			@readfile($file);
		}
	}

    function upload($id=0) {
        $vidcam = O('vidcam', $id);
        $form = Input::form();
        $key = $form['key'];
        if (!$vidcam->id || $key != $vidcam->capture_key) {
            return FALSE;
        }

        $tmp_file = $_FILES['image']['tmp_name'];

        if ($tmp_file) {
            $now = Date::time();

            // 临时兼容流媒体模式
            // 使用流媒体播放的摄像头，不使用定时截图机制
            // 由于 NVR 型号的限制，部分型号不支持历史记录截图，所以此块改进先注释掉
            // if ($vidcam->type == Vidcam_Model::TYPE_GENEE) {
                //前台页面显示监控图片存储
                $file = Vidmon::video_file($vidcam);
                File::check_path($file);
                @move_uploaded_file($tmp_file, $file);
            // }

            $fp = fopen(Config::get('system.tmp_dir'). Misc::key('vidcam', $vidcam->id), 'w+');

            if ($fp) {
                flock($fp, LOCK_EX);

                $alarmed_upload_timeout = Config::get('vidmon.alarmed_capture_timeout'); 

                if ($alarmed_upload_timeout > ($now - $vidcam->last_alarm_time)) {
                    $upload_duration = Config::get('vidmon.alarmed_capture_duration');
                }
                else {
                    $upload_duration = Config::get('vidmon.capture_duration');
                }

                if ($now - $vidcam->last_upload_time >= $upload_duration) {
                    //存储截图数据对象
                    $vidcam_capture_data = O('vidcam_capture_data');
                    $vidcam_capture_data->vidcam = $vidcam;
                    $vidcam_capture_data->ctime = $vidcam->last_capture_time;     //存储capture时间，同时对应了某个文件

                    //警报点间隔时间起始时间 (当前时间 - 单侧时间)
                    $alarm_duration_dtstart = $now - Config::get('vidmon.alarm_capture_time');

                    //判断是否属于某个alarm对象时间之后的某个保存范围内的capture，如果属于某个alarm范围内，则设定is_alarm为true， 反之为false
                    $is_alarm = Q("vidcam_alarm[vidcam={$vidcam}][ctime={$alarm_duration_dtstart}~{$now}]")->total_count() ? TRUE : FALSE;

                    $vidcam_capture_data->is_alarm = $is_alarm;
                    $vidcam_capture_data->save();

                    $vidcam->last_upload_time = $now;
                    $vidcam->save();
                    
                    /* 将报警时间间隔之前的所有非is_alarm的图片和数据进行删除 */
                    Vidmon::delete_capture_data($vidcam, $alarm_duration_dtstart);

                    // 由于 NVR 型号的限制，部分型号不支持历史记录截图，所以此块改进先注释掉
                    // 使用流媒体播放的摄像头，不使用定时截图机制
                    // if ($vidcam->type == Vidcam_Model::TYPE_GENEE) {
                        //存储capture图片
                        $capture_file = Vidmon::video_capture_file($vidcam, $vidcam->last_capture_time);
                        // Log::add(sprintf('[vidmod]%s图片路径', $capture_file), 'devices');
                        File::check_path($capture_file);                    
                        $im = ImageCreateFromJpeg($file);
                        $bg = imagecolorallocate($im, 255, 255, 255);
                        $black = imagecolorallocate($im, 0, 0, 0);
                        $str = (string)Date::format($vidcam->last_capture_time, 'Y-m-d H:i:s');
                        imagestring($im, 5, 26, 26, $str, $black);
                        imagestring($im, 5, 25, 25, $str, $bg);
                        if (imagejpeg($im, $capture_file)) {
                            //存储缩略图
                            $thumbnail_file = Vidmon::video_alarm_thumbnail_file($vidcam, $vidcam->last_capture_time);
                            File::check_path($thumbnail_file);

                            //载入、重新设定大小、保存到缩略图目录
                            $image = Image::load($file);
                            $image->resize(Config::get('vidmon.thumbnail_width'), Config::get('vidmon.thumbnail_height'));
                            $image->save('jpg', $thumbnail_file);
                        }
                        else {
                            $vidcam_capture_data->delete();
                        }
                    // }
                }
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
    }

    function history($id = 0) {
        $vidcam = O('vidcam', $id);
        $form = Input::form();
        $key = $form['key'];
        if (!$vidcam->id || $key != $vidcam->capture_key) {
            return FALSE;
        }

        $tmp_file = $_FILES['image']['tmp_name'];

        if ($tmp_file) {
            $now = Date::time();

            //前台页面显示监控图片存储
            $file = Vidmon::video_file($vidcam);
            File::check_path($file);
            @move_uploaded_file($tmp_file, $file);

            $fp = fopen(Config::get('system.tmp_dir'). Misc::key('vhistory', $vidcam->id), 'w+');

            if ($fp) {
                flock($fp, LOCK_EX);

                //存储截图数据对象
                $vidcam_capture_data = O('vidcam_capture_data');
                $vidcam_capture_data->vidcam = $vidcam;
                $vidcam_capture_data->ctime = $vidcam->last_capture_time;     //存储capture时间，同时对应了某个文件

                //警报点间隔时间起始时间 (当前时间 - 单侧时间)
                $alarm_duration_dtstart = $now - Config::get('vidmon.alarm_capture_time');

                //判断是否属于某个alarm对象时间之后的某个保存范围内的capture，如果属于某个alarm范围内，则设定is_alarm为true， 反之为false
                $is_alarm = Q("vidcam_alarm[vidcam={$vidcam}][ctime={$alarm_duration_dtstart}~{$now}]")->total_count() ? TRUE : FALSE;

                $vidcam_capture_data->is_alarm = $is_alarm;
                $vidcam_capture_data->save();

                $vidcam->last_upload_time = $now;
                $vidcam->save();
                
                /* 将报警时间间隔之前的所有非is_alarm的图片和数据进行删除 */
                Vidmon::delete_capture_data($vidcam, $alarm_duration_dtstart);

                //存储capture图片
                $capture_file = Vidmon::video_capture_file($vidcam, $vidcam->last_capture_time);
                File::check_path($capture_file);                    
                $im = ImageCreateFromJpeg($file);
                $bg = imagecolorallocate($im, 255, 255, 255);
                $black = imagecolorallocate($im, 0, 0, 0);
                $str = (string)Date::format($vidcam->last_capture_time, 'Y-m-d H:i:s');
                imagestring($im, 5, 26, 26, $str, $black);
                imagestring($im, 5, 25, 25, $str, $bg);
                if (imagejpeg($im, $capture_file)) {
                    //存储缩略图
                    $thumbnail_file = Vidmon::video_alarm_thumbnail_file($vidcam, $vidcam->last_capture_time);
                    File::check_path($thumbnail_file);

                    $image = Image::load($file);
                    $image->resize(Config::get('vidmon.thumbnail_width'), Config::get('vidmon.thumbnail_height'));
                    $image->save('jpg', $thumbnail_file);
                }
                else {
                    $vidcam_capture_data->delete();
                }

                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
    }

    function preview($id=0) {

        $vidcam = O('vidcam', $id);

        //直接跳转
        if (!$vidcam->id) URI::redirect('/');

        $file = Vidmon::video_file($vidcam);
        File::check_path($file);

        $me = L('ME');
        $has_access = $me->is_allowed_to('查看', $vidcam);

        if ($has_access && file_exists($file) && time() - filemtime($file) < 10) {
            header('Content-Type: image/jpeg');
            @readfile($file);
        }
        else {
			$file = Core::file_exists(PUBLIC_BASE.'images/capture_blank.gif', 'vidmon');
			header('Content-Type: image/gif');
			@readfile($file);
        }

    }
}
