<?php

class Capture_Controller extends Controller {

	const MAX_CHANNELS = 5;
	
	private function _capture_file($equipment) {
		$dir = Config::get('system.tmp_dir').'capture/';
		return $dir . "equipment.{$equipment->id}.png";
	}

	function index($id = 0) {

		try {
			$form = Input::form();
			$equipment = O('equipment', $id);
			$me = L('ME');
			
			if (!$equipment->id
				|| !$me->id
				|| !$equipment->connect
				|| $equipment->status != EQ_Status_Model::IN_SERVICE
			) {
				throw new Error_Exception;
			}

			if (!$me->is_allowed_to('监控', $equipment)) {
				throw new Error_Exception;
			}

			$now = Date::time();
			$capture_file = $this->_capture_file($equipment);
			
			// 仅查看10s内的更新图片
			if (file_exists($capture_file) && time() - filemtime($capture_file) < 10 ) {
				header('Content-Type: image/jpeg');
				@readfile($capture_file);
			}
			else {
				$path = Core::file_exists(PUBLIC_BASE.'images/capture_blank.gif', 'eq_mon');
				header('Content-Type: image/gif');
				@readfile($path);
			}
		}
		catch (Error_Exception $e) {
			$path = Core::file_exists(PUBLIC_BASE.'images/capture_error.gif', 'eq_mon');
			header('Content-Type: image/gif');
			@readfile($path);
		}
		exit;
	}

	function upload($id = 0) {
		//上传屏幕截图的处理
		try {
			$equipment = O('equipment', $id);
			if (!$equipment->id) throw new Error_Exception;

			$now = time();
			$capture_file = $this->_capture_file($equipment);
			$form = Input::form();
			$client = new Equipment_Client($equipment);
			$result = $client->monitor_upload($form, $capture_file);
			return $result;
		}
		catch (Error_Exception $e) {
			return false;
		}
	}

}
