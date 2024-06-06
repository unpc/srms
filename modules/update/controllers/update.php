<?php

class Update_AJAX_Controller extends AJAX_Controller{


	/* TASK #1303::Update更新需要能够清除全部(kai.wu@2011.08.10) */
	function index_updates_delete() {
		$form = Input::form();
		$tab = $form['tab'];
		$me = L('ME');
		if (JS::confirm(I18N::T('update', '您确定要删除所有信息吗?'))){
			if (Update::delete_all_updates($tab)) {
				Lab::message(Lab::MESSAGE_NORMAL, I18N::T('update', '消息清除成功!'));
			}else {
				Lab::message(Lab::MESSAGE_EORROR, I18N::T('update', '清除清除失败!'));
			}
			JS::refresh();
		}
	}
	
	function index_update_delete() {
		$form = Input::form();
		$ids = (array) $form['ids'];
		$me = L('ME');
		/*
		NO.BUG#136（guoping.zhang@2010.11.11)
		删除update消息前，应消息提醒
		*/
		if (JS::confirm(I18N::T('update', '您确定要删除这条信息吗?'))){
			foreach($ids as $id) {
				$update = O('update', $id);
				if ($update) {
					$me->connect($update, 'read');
				}
			}
		}
		
		/*
		NO.BUG#099
		2010.11.05
		张国平
		删除完tab下所有更新记录后，显示出无内容的空tab，不跳转到默认的用户信息页面(bug修复)
		*/
		$update_infos = $updates = Update::fetch(0, 10, $start, $update->object->name());
		if (count($update_infos)){
			JS::refresh();
		}
		else {
			JS::redirect(URI::url('/'));
		}
	}
	
	function index_more_updates_click() {
		$form = Input::form();
		$uniqid = $form['more_id'];
		$start = $form['start'];
		$time = $form['stime'];
		$model_name = $form['model_name'];
		$updates = Update::fetch($start, 10, $next_start, $model_name);
		if (!count($updates)) {
			Output::$AJAX["#$uniqid"] = ['data'=>'', 'mode'=>'replace'];
			return;
		}

		Output::$AJAX["#$uniqid"] = [
			'data' => (string)V('update:desktop/list', ['updates'=>$updates, 'next_start'=>$next_start, 'time'=>$time, 'model_name'=>$model_name]),
			'mode' => 'replace',
		];
		
	}
	
}
