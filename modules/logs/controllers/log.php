<?php 
class Log_Controller extends Controller{
	function index() {
		$me = L('ME');
		
		if ( !in_array($me->token, Config::get('log.admin')) )  URI::redirect('error/401');

		$form = Input::form();
		$name = $form['name'];
		$path = Logs::get_log_path();	
		
		//批量下载
		if (count($form['select'])>0 && !$name ) {
			Log::add(sprintf('[nfs] %s[%d] 批量下载了 %s 目录下的 %s',
									 $me->name, $me->id,
									 $full_path, join(', ', $form['select'])),
							 'journal');
			
			$this->_index_zip($path,$form['select'], $form['download_type'] == 'win');
			exit;
		} 
		else {
			if ( !$name ) URI::redirect('error/404');
			$full_path = Logs::get_log_path($name);
			//单个文件下载
			if (is_file($full_path)) {
				Downloader::download($full_path, TRUE);
				Log::add(sprintf('[nfs] %s[%d] 下载了 %s',
									 $me->name, $me->id, $full_path),
							 'journal');
	
				exit;
			}
		
		}
	}
	
	//批量下载文件
	private function _index_zip($path,$selected_names, $win) {
		// 下载文件
		$zip = new ZipArchive;
		$temp_file = tempnam(sys_get_temp_dir(), 'LIMS');
		if ($zip->open($temp_file, ZIPARCHIVE::CREATE) === TRUE) {
			foreach ($selected_names as $name) { 
				File::traverse($path.'/'.$name, 'Log_Controller::_zip_traverse', ['base'=>$path, 'zip'=>$zip, 'win'=>$win]);
			}
			$zip->close();
			$filename = File::basename($path);
			
			if (!$filename) $filename = 'logs';

			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: application/zip");
			header("Content-Transfer-Encoding: binary");
			header("Content-Disposition: attachment; filename=".$filename.".zip");

			header("Content-Description: File Transfer");

			@readfile($temp_file);
			@unlink($temp_file);
			exit();
		}
	}
	static function _zip_traverse($path, $params) {
        $zip = $params['zip'];
        $win = $params['win'];
        $name = File::relative_path($path, $params['base']);
        if ($win) {
            $name = iconv('UTF-8', 'GB2312', $name);
        }

		//如果为文件，增加文件
		if (is_file($path)) {
			/*
				NO. BUG#164 (Cheng.Liu@2010.11.13)
				由于ubuntu中文文件名编码存在问题，导致此BUG无法彻底解决
				暂时也urlencode方式转化文件名.
			*/
			/* NO. BUG#164 (Jia Huang@2010.11.13)
			将%2F替换回'/' 用于保证目录结构
			*/

			$zip->addFile($path, $name);
		}
		//其他为目录，增加空目录
		else {
			$zip->addEmptyDir($name);
		}

		return $zip;
	}
}

class Log_AJAX_Controller extends AJAX_Controller {
	function index_log_list_refresh () {
		$form = Input::form();
		$log_path = Config::get('log_path.path');
		$log_list = Logs::log_list($log_path, NULL);
		
		Output::$AJAX['#'.$form['tbody_log_list']] = [
			'data' => (string)V('logs:log/log', ['logs' => $log_list, 'tbody_log_list_id' => $form['tbody_log_list'],]),
			'mode' => 'replace'
		];
	}
}
