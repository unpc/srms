<?php

class Index_Controller extends Base_Controller {

	function index() {
		URI::redirect(URI::url('!test/index/lists'));
	}



	function lists() {
		$me = L('ME');
		if (!$me->is_allowed_to('执行', 'test')) URI::redirect('error/401');
		
		
		$form = Lab::form(); 
		
				
		//路径lists.upgrade%F.2.3%F   每个目录用%F分开
		$args = func_get_args(); 
		$path_dirs = test::path_fix($args);			
		$cli_path = Config::get('cli_path.default_path');
		$path = $cli_path.$path_dirs;
	
		if(!is_dir($path)) {
			URI::redirect(URI::url('!test/index/lists'));
		}	
		$file_list = NFS::file_list($path, NULL);
		
		
		
		//搜索	
		$reg='/'.$form['name'].'/';
		foreach($file_list as $file){
			if(preg_match($reg,$file['name'])){
				$files[] = $file;
			}
		}
		
		
		//分页
		$start = (int) $form['st'];
		$per_page = 30;
		$start = $start - ($start % $per_page);
		$pagination = Test::pagination_array($files, $start, $per_page);
		
		$files = Test::pagination_slice($files, $start, $per_page);

		$this->layout->body->primary_tabs
			 ->select($tabs)
			 ->content = V('test:index', [
			 		'form' => $form,
					'files' => $files,
					'path_dirs' => $path_dirs,
					'pagination' => $pagination,
						]);
	}	
	
	
	
}
