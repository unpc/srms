<?php

class DC_Record_Controller extends Base_Controller {

       function index() {
               $content = DC_Record::index_records_get();
               $this->layout->body->primary_tabs
					   ->select('record')
					   ->set('content',$content);
       }
               
}

class DC_Record_AJAX_Controller extends AJAX_Controller {

	function index_export_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$door = $form['door'];
		
		$columns = Config::get('entrance.export_columns.entrance');
		switch ($type) {
			case 'csv':
				$title = I18N::T('entrance', '请选择要导出Excel的列');
				break;
			case 'print':
				$title = I18N::T('entrance', '请选择要打印的列');
				break;
		}
		JS::dialog(V('export_form', [
						'form_token' => $form_token,
						'columns' => $columns,
						'type' => $type,
						'door' => $door,
					]), [
						'title' => $title
					]);	
	}
	
	function index_delete_record_click() {
		$form = Input::form();
		$uniqid = $form['uniqid'];
		/*
			BUG#98
			2010.11.04 by cheng.liu
			判断是否存在uniqid，如若不存在，则直接退出
			避免出现无法刷新页面现象
		*/
		if (!$uniqid) {
			return;
		}

		$dc_record = O('dc_record',$form['id']);
		
		if (!L('ME')->is_allowed_to('删除', $dc_record)) {
			return;
		}

		if(JS::confirm(I18N::T('entrance','你确定要删除吗? 删除后不可恢复!'))) {
			
			if($dc_record->delete()) {
				/* 记录日志 */			
				Log::add(strtr('[entrance] %user_name[%user_id] 删除了一条进出记录: %dc_record_user_name[%dc_record_user_id] 于 %date %direction %door_name[%door_id]', [
							'%user_name' => L('ME')->name,
							'%user_id' => L('ME')->id,
							'%dc_record_user_name' => $dc_record->user->name,
							'%dc_record_user_id' => $dc_record->user->id,
							'%date' => Date::format($dc_record->time, 'Y/m/d H:m:s'),
							'%direction' =>  DC_Record_Model::$direction[$dc_record->direction],
							'%door_name' => $dc_record->door->name,
							'%door_id' => $dc_record->door->id,
				]), 'journal');

				JS::refresh();
				/* Output::$AJAX['#'.$uniqid] = array( */
				/* 		'data'=>'', */
				/* 		'mode'=>'replace', */
				/* 	); */
			}
		}		
	}
} 
