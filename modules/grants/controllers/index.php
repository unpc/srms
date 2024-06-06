<?php

class Index_Controller extends Controller {
	function index() {
		URI::redirect('!grants/grants');
	}
}

class Index_AJAX_Controller extends AJAX_Controller {
	// grant 下拉框 change  处理函数
	function index_grant_select_change(){
	
		$form = Input::form();
		
		$grant_name = $form['grant_name'] ?: 'grant';
		$portion_name = $form['portion_name'] ?: 'grant_portion';
		$grant = O('grant', $form[$grant_name]);
		$uniqid = $form['portion_uniqid'];
		$no_balance = !!$form['no_balance'];

		Output::$AJAX['.'.$uniqid] = (string) V('grants:widgets/portion_select', [
			'grant'=>$grant, 'portion_uniqid'=>$uniqid, 
			'portion_name'=>$portion_name, 'no_balance'=>$no_balance]);
	}

	function index_grant_portion_change() {

		$form = Input::form();

		$portion_name = $form['portion_name'] ?: 'grant_portion';
		$portion = O('grant_portion', $form[$portion_name]);
		
		$uniqid = $form['balance_uniqid'];

		Output::$AJAX['.'.$uniqid] = $portion->id ? I18N::HT('grants', '可用余额 %num', ['%num'=>Number::currency($portion->avail_balance)]) : (string) V('form_require');

	}
	
	//导出、打印。点击导出、打印链接会触发该事件
	function index_export_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$columns = Config::get('grants.export_columns.grant');
		
		if ($type=='csv') {
			$title = I18N::T('grants','请选择要导出CSV的列');		
		}
		elseif ($type=='print')
		{
			$title = I18N::T('grants', '请选择要打印的列');
		}
		JS::dialog(V('export_grant_form',[
			'form_token' => $form_token,
			'columns' => $columns,
			'type' => $type
		]),[
			'title' => I18N::T('grants',$title)
		]);

	}


}
