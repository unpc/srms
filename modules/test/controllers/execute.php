<?php
class Execute_AJAX_Controller extends AJAX_Controller {

	function index_execute_file_click() {
		$form = Form::filter(Input::form());
		$path['file'] = $form ['file'];
		$path['dir'] = $form['dir'];
		JS::dialog(V('test:exec_dialog', ['path'=>$path]), ['width'=>300]);
		
	}
	
	function index_execute_file_submit() {
		$form = Form::filter(Input::form());
		$cli_path = Config::get('cli_path.default_path');
		
		$path['file'] = $form ['file'];
		$path['dir'] = $form['dir'];
		$otherargs = $form['otherargs'];
		$otherarg = $otherargs[1] ? implode(' ',$otherargs) : '';
		$execute = 'cd '.$cli_path.$path['dir'].';SITE_ID='.SITE_ID.' LAB_ID='.LAB_ID.' php '.$path['file'];
		$execute .=  ' '.$otherarg;
		
		exec($execute, $result, $x);
			
		$remove = ['[0m','[1m','[31m','[32m'];
		$result = $result ? str_replace($remove, '',$result) : '没有结果被返回';
		JS::dialog(V('test:exec_dialog', ['path'=>$path, 'result'=>$result, 'otherargs'=>$otherargs]), ['width'=>300]);
		
	}	
}
