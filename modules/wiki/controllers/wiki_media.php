<?php

abstract class Wiki_Media_AJAX_Controller extends AJAX_Controller {
	
	protected $root;
	
	function dialog() {
		$view = V('wiki:wiki_media/dialog');
		$view->root = $this->root;
		$view->dir_view = V('wiki:wiki_media/dir');
		$view->list_view = V('wiki:wiki_media/list');
		File::check_path($this->root);
		JS::dialog($view);
	}
	
	function index_attachment_dir_click() {
		$list_view = V('wiki:wiki_media/list');
		$list_view->root = $this->root;
		$list_view->base = Input::form('base').'/';
		Output::$AJAX['#attachment_list']=(string)$list_view;		
	}
	
	function index_attachment_delete_click() {
		File::delete($this->root.Input::form('path'), TRUE);
		Output::$AJAX['#attachment_'.md5(Input::form('path'))]=['data'=>'', 'mode'=>'replace'];
	}
	
	function index_attachment_submit() {
		$file = Input::file('attachment');
		if ($file) {
			$filename = $file['name'];
			$path = $this->root.Input::form('base').$filename;
			File::check_path($path);
			/*
			if (file_exists($path)) {
				//有重名文件, 自动找到文件名最后数字并递增

				if (preg_match('/(\.\d+)(\.[^.])?$/', $filename, $parts)) {
					$index = $parts[1];
				} else {
					$index = 0;
				}

				do {
					$index ++;
					$path = $this->root.Input::form('base').preg_match('/^(.+)(\.\d+)?(\.[^.])?$/', '$1.'.strval($index).'$3', $filename);
				} 
				while (file_exists($path));
			}*/

			if (move_uploaded_file($file['tmp_name'], $path)) {
				$this->index_attachment_dir_click();
			}
		}
	}
	
}
