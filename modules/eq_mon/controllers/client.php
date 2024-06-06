<?php

class Client_Controller extends Controller {
	
	private function _client_file($equipment) {
		$dir = Config::get('system.tmp_dir').'client/';
		File::check_path($dir);
		return $dir . "equipment.{$equipment->id}.png";
	}
	
	function index($id = 0) {

		try {
			$form = Input::form();
			$equipment = O('equipment', $id);
			$me = L('ME');
			
			if (!$equipment->id || !$equipment->connect) {
				throw new Error_Exception;
			}

			if (!$me->id) {
				throw new Error_Exception;
			}

			if ($equipment->status != EQ_Status_Model::IN_SERVICE || !$equipment->connect) {
				throw new Error_Exception;
			}

			$now = Date::time();
			$client_file = $this->_client_file($equipment);
			// 仅查看10s内的更新图片
			if (file_exists($client_file) && time() - filemtime($client_file) < 10 ) {
				header('Content-Type: image/png');
				@readfile($client_file);
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
	
	function upload($id=0) {
		try {
			$form = Input::form();
			$equipment = O('equipment', $id);
			if (!$equipment->id) throw new Error_Exception;

			$now = time();
			$client_file = $this->_client_file($equipment);
			
			$content = $form['content'];
			
			if ($content) {
				$data = base64_decode($content);
				File::check_path($client_file);
				file_put_contents($client_file, $data);
			}
		}
		catch (Error_Exception $e) {
			
		}
	}
}
