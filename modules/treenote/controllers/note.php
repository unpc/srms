<?php

class Note_Controller extends Base_Controller {

	function index($id=0) {

		$note = O('tn_note', $id);
		if (!$note->id) {
			URI::redirect('error/404');
		}
		
		if (!L('ME')->is_allowed_to('查看', $note)) {
			URI::redirect('error/401');
		}

		$content = V('note/index', ['note' => $note]);

		$this->add_css('rte_container');
		$this->add_js('rte rte.toolbar');
		$this->layout->body->primary_tabs
			->add_tab('note_view', [
				 '*' => $note->breadcrumb()
			])
			->select('note_view')
			->set('content', $content);
	}

	function export_stat() {
		$form = Form::filter(Input::form());
		$type = $form['type'];
		$form_token = $form['form_token'];
		if ($type == 'csv') {
			$this->_export_stat_csv($form_token, $tab);
		}
		
	}
	
	private function _export_stat_csv($form_token, $tab) {
		
		$valid_columns = Config::get('treenote.export_columns.work');
		$visible_columns = Input::form('columns');
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

		$form = $_SESSION[$form_token];

		$user = O('user', $form['user']);
		$notes = Q($form['selector']);

		$fnotes = [];
		foreach ($notes as $n) {
			if ($fnotes[$n->task->id]) {
				$fnotes[$n->task->id]->actual_time += $n->actual_time;
			}
			else {
				$fnotes[$n->task->id] = $n;
			}
		}
		
		$csv = new CSV('php://output', 'w', $file_name);
		$csv->write(I18N::T('treenote',$valid_columns));
			
		foreach ($fnotes as $n) {
			$data = [];
			foreach ($valid_columns as $key => $value) {
				switch ($key) {
					case 'title':
						$data[] = $n->task->title;
						break;
					case 'user':
						$data[] = $n->user->name;
						break;
					case 'expected_time':
						$data[] =round($n->task->expected_time/3600, 1);
						break;
					case 'actual_time':
						$data[] =round($n->actual_time/3600, 1);
						break;
					case 'deadline':
						$data[] = Date::format($n->task->deadline, 'Y/m/d');
						break;
					case 'status':
						$data[] = TN_Task_Model::$status_options[$n->task->status];
						break;
				}
			}
			$csv->write($data);
		}
		
		$csv->close();
	}
	
}

class Note_AJAX_Controller extends AJAX_Controller {

	function index_export_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$columns = Config::get('treenote.export_columns.work');
		switch ($type) {
			case 'csv':
				$title = I18N::T('entrance', '请选择要导出CSV的列');
				break;
			case 'print':
				$title = I18N::T('entrance', '请选择要打印的列');
				break;
		}
		JS::dialog(V('export_form', [
						'type' => $type,
						'form_token' => $form_token,
						'columns' => $columns
					]),[
						'title' => $title
					]);
	}

	function index_new_note_click() {
		$me = L('ME');
		$form = Input::form();
		$task = O('tn_task', $form['task']);
		if ($me->id && $me->is_allowed_to('添加记录', $task)) {
			JS::dialog(V('treenote:note/quick_form', ['task'=>$task]), ['title'=>I18N::HT('treenote', '添加记录'), 'drag'=>TRUE]);
		}
	}

	function index_edit_note_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$note = O('tn_note', $form['id']);
		if ($me->id && $note->id && $me->is_allowed_to('修改', $note)) {
			$form['title'] = $note->title;
			$form['content'] = $note->content;
			$form['actual_hours'] = round($note->actual_time / 3600, 1);
			JS::dialog(V('treenote:note/quick_form', ['form'=>$form, 'note'=>$note, 'task'=>$note->task]), ['title'=>I18N::HT('treenote', '修改记录'), 'drag'=>TRUE]);
		}
	}

	function index_note_form_submit() {

		$me = L('ME');
		if (!$me->id) {
			return;
		}

		$form = Form::filter(Input::form());

		$note = O('tn_note', $form['id']);
		if ($note->id) {
			if (!$me->is_allowed_to('修改', $note)) {
				return;
			}
			$add_op = FALSE;
			$dialog_title = '修改记录';
		}
		else {
			$task = O('tn_task', $form['task']);
			if (!$me->is_allowed_to('添加记录', $task)) {
				return;
			}
			$add_op = TRUE;
			$dialog_title = '添加记录';
		}


		/* validation */
		$form
			->validate('content', 'not_empty', I18N::T('treenote', '请填写内容!'));

		if ($add_op) {
			$task = O('tn_task', $form['task']);
			if (!$task->is_editable()) {
				$form->set_error('task', I18N::T('treenote', '任务已锁定，不能添加记录！'));
			}
		}
		else {
			$task = $note->task;
		}

		if ($form->no_error) {

			if ($add_op) {
				$note->user = $me;
				$note->task = $task;
			}
			$note->content = $form['content'];
			$note->actual_time = 3600 * floatval($form['actual_hours']);

			$note->save();
			if ($note->id) {

				/* 记录日志 */
                if ($add_op) {
                    Log::add(strtr('[treenote] %user_name[%user_id]添加了记录%note_title[%note_id]', [
                        '%user_name'=> L('ME')->name,
                        '%user_id'=>  L('ME')->id,
                        '%note_title'=> $note->title,
                        '%note_id'=> $note->id
                    ]), 'journal');
                }
                else {

                    Log::add(strtr('[treenote] %user_name[%user_id]编辑了记录%note_title[%note_id]', [
                        '%user_name'=> L('ME')->name,
                        '%user_id'=>  L('ME')->id,
                        '%note_title'=> $note->title,
                        '%note_id'=> $note->id
                    ]), 'journal');
                }

				if ($add_op) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('treenote', '记录添加成功！'));
				}
				else {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('treenote', '记录更新成功！'));
				}

				JS::refresh();
			}
			else {
				if ($add_op) {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('treenote', '记录添加失败！'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('treenote', '记录更新失败！'));
				}
			
				JS::dialog(V('treenote:note/quick_form', ['form'=>$form, 'note'=>$note, 'task'=>$task]), ['title'=>I18N::HT('treenote', $dialog_title), 'drag'=>TRUE]);
			}

		}
		else {
			JS::dialog(V('treenote:note/quick_form', ['form'=>$form, 'note'=>$note, 'task'=>$task]), ['title'=>I18N::HT('treenote', $dialog_title), 'drag'=>TRUE]);
		}

	}

	function index_delete_note_click() {
		if (JS::confirm(I18N::T('treenote', '您确定希望删除该记录吗?'))) {
			$id = Input::form('id');
			$note = O('tn_note', $id);
			if ($note->id && L('ME')->is_allowed_to('删除', $note)) {
				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]删除了记录[%note_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=> L('ME')->id,
                    '%note_id'=> $note->id
                ]), 'journal');

				$note->delete();
				JS::refresh();
			}
			else {
				JS::alert(I18N::T('treenote', '无法删除该记录!'));
				JS::close_dialog();
			}
		}

	}

}
