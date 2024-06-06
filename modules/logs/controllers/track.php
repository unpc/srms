<?php 
class Track_Controller extends Layout_Controller {

	/*
function index() {
		$form = Input::form();
		$name = $form['name'];
		$mtime = $form['mtime'];
		
		$path = Logs::get_log_path(NULL);	
		
		if (!isset($command)) {
			$command = 'cd '.$path.'; tail '.$name.' -n 20';
		}
		$this->_track($command, $mtime, $path.'/'.$name);
	}
	
	
	//跟踪文件
	function _track($command='tail', $mtime, $full_path) {

		exec($command, $out_put, $return_val);
		if ($return_val) {
			URI::redirect('error/404');
		}
		$this->layout = V('logs:track/track',array(
			'log_content' => $out_put,
			'mtime' => $mtime,
			'full_path' => $full_path,
		));
	}
*/
}
class Track_AJAX_Controller extends AJAX_Controller {

	/*
function index_content_refresh() {
	
		$form = Input::form();
		$mtime = filemtime($form['path']);

		if ($mtime==$form['mtime']) {
			return;
		}

		$command = 'tail '.$form['path'].' -n 20';
		exec($command, $output, $return_val);	
		
		Output::$AJAX['#'.$form['tbody']] = array(
			'data' => (string) V('logs:track/content', array(
					'log_content'=>$output,
					'mtime' => $mtime,
					'full_path' => $form['path']
			)),
			'mode' => 'replace',
		);
	}
*/
}