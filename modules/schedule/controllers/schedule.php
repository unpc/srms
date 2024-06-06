<?php

class Schedule_Controller extends Base_Controller {

	function download($id=0) {
		/*
		NO.TASK#236（guoping.zhang@2010.11.18)
		新权限判断规则
		NO.BUG#178（guoping.zhang@2010.11.20)
		查看日程附件时is_allowed_to的客体是component对象
		*/
		$component = O('cal_component', $id);
		$form = Form::filter(Input::form());
		$calendar = O('calendar', $form['cal_id']);
		$component->calendar = $calendar;
		if (!L('ME')->is_allowed_to('查看附件', $component)) {
			URI::redirect('error/404');
		}
		
		if($id > 0) {
			$path = Schedule::get_path('upload', $id.'/');
		}
		else {
			$path = Schedule::get_path('tmp', L('ME')->id.'/');
		}
		$name = $form['file_name'];
		$full_path = $path.$name;
		Downloader::download($full_path, FALSE);
		exit;
	}


}

class Schedule_AJAX_Controller extends AJAX_Controller {
	
	function index_component_attachments_submit($cal_id=0) {
		$form = Form::filter(Input::form());
		$component_id = $form['container'];
		$component = O('cal_component',$component_id);
		$added_files = Input::file('file');
		$container_id = $form['container_id'];
		$calendar = O('calendar', $cal_id);
		$component->calendar = $calendar;
		/*
		NO.BUG#168(guoping.zhang@2010.11.13)
		对实验室日程附件操作权限设置：
		NO.TASK#236（guoping.zhang@2010.11.18)
		新权限判断规则
		NO.BUG#178（guoping.zhang@2010.11.20)
		添加日程附件时is_allowed_to的客体是component对象
		*/
		$me = L('ME');
		if (!$me->is_allowed_to('添加附件', $component)) return;
		
		if($component->id) {
			$path = Schedule::get_path('upload', $component->id.'/foobar');			
			$file_path = Schedule::get_path('upload', $component->id.'/');
			$name = $added_files['name'];
			File::check_path($path);
		
			if ($name) {
				$type = $added_files['type'];
				$tmp_name = $added_files['tmp_name'];
				$error = $added_files['error'];
				$size = $added_files['size'];
				move_uploaded_file($tmp_name, $file_path.$name);
			}
		}
		elseif ($added_files) {
			$path = Schedule::get_path('tmp', $me->id.'/foobar');
			$file_path = Schedule::get_path('tmp', $me->id.'/');
			$name = $added_files['name'];
			File::check_path($path);
			$type = $added_files['type'];
			$tmp_name = $added_files['tmp_name'];
			$error = $added_files['error'];
			$size = $added_files['size'];
			move_uploaded_file($tmp_name, $file_path.$name);
		}
		
		Output::$AJAX['#'.$container_id] = [
			'data'=>(string)V('schedule:calendar/component_form/attachments_files',[
				'component'=>$component,
				'container'=>$container_id							
			]),
		];
		
	}
	
	/*
	NO.BUG#135（guoping.zhang@2010.11.11)
	删除附件前，要进行确认提示
	NO.BUG#168(guoping.zhang@2010.11.13)
	对实验室日程附件操作权限设置：
	NO.TASK#236（guoping.zhang@2010.11.18)
	新权限判断规则
	NO.BUG#178（guoping.zhang@2010.11.20)
	删除日程附件时is_allowed_to的客体是component对象
	*/
	function index_attachments_delete_click() {
		$form = Form::filter(Input::form());
		$container = $form['container'];
		$component_id = $form['component'];
		$component = O('cal_component', $component_id);
		$calendar = O('calendar', $form['cal_id']);
		$component->calendar = $calendar;
		$me = L('ME');
		
		if (!$me->is_allowed_to('删除附件', $component)) return;
		
		if (!JS::confirm(I18N::T('schedule', '您确定要删除该附件吗？'))) return;
		
		if($component->id>0) {
			$path = Schedule::get_path('upload', $component->id.'/');
		}
		else {
			$path = Schedule::get_path('tmp', L('ME')->id.'/');
		}
		
		File::delete($path.$form['file']);
		
		Output::$AJAX['#'.$container] = [
			'data'=>(string)V('schedule:calendar/component_form/attachments_files',[
				'component'=>$component,
				'container'=>$container							
			]),
		];
	}
}
