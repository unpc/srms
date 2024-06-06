<?php

class Capture_Controller extends Controller {

	function index($id = 0, $data_id = 0) {
	
		/*
		目前我们利用缓存的机制来进行处理，故不需要进行试试抓取数值的处理。
		
		try {
			
			$vidcam_data = O('vidcam_capture_data', $data_id);
			
			if (!$vidcam_data->id) throw new Error_Exception('error data!');
			
	        $dir = Config::get('vidmon.capture_path'). $id . '/thumbnail/';
	        $dir = $dir.date('m/d', $vidcam_data->ctime);

	        $capture_file =  $dir.'/'. $vidcam_data->ctime. '.jpg';
	        
			
			// 仅查看10s内的更新图片
			if (file_exists($capture_file)) {
				header('Content-Type: image/jpeg');
				@readfile($capture_file);
			}
			else {
			}
		}
		catch (Error_Exception $e) {
		}
		exit;		
		*/
	}	
}
