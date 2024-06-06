<?php

class User_Controller extends Layout_Controller {

    function print() {

        $tip = Config::get('comment.rate')['tip'];
        $form = Form::filter(Input::form());
        $form_token = $form['form_token'];
        $tab = $form['tab'];

		if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_comment', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
		$type = $form['type'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        if ($tab == 'incharge') {
            $valid_columns = Config::get('comment.export_columns.incharge');
        } elseif ($tab == 'user') {
            $valid_columns = Config::get('comment.export_columns.user');
        }
		$visible_columns = $form['columns'] ? : $form['@columns'];

		foreach ($valid_columns as $p => $p_name ) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
        }

        $comments = Q($form['selector']);

        $this->layout = V("eq_comment:print/{$tab}", [
            'tip' => $tip,
            'comments' => $comments,
            'valid_columns' => $valid_columns
        ]);
    }

}

class User_AJAX_Controller extends AJAX_Controller {

    public function index_comment_user_click() {
        $form = Form::filter(Input::form());
        $object = O($form['object_name'], $form['object_id']);
        $comment = O('eq_comment_user', ['source' => $object]);

        JS::dialog(V('comment/add.user', [
            'object' => $object,
            'comment' => $comment,
        ]), [
            'width' => 370, 
            'title' => '使用评价'
        ]);
    }

    public function index_comment_user_submit() {
		$me = L('ME');
		$form = Form::filter(Input::form());
        $object = O($form['object_name'], $form['object_id']);
        
		if (!$form['submit'] || !$object->id || !$me->is_allowed_to('评价', $object)) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_comment', '您无权进行操作!')); 
            JS::refresh();
            return;
        };

        $min = min($form['user_attitude'], $form['user_proficiency'], $form['user_cleanliness'], $form['test_importance']);
        if ($min < 5 && $form['test_remark'] == '') $form->set_error('test_remark', T('有评价小于五星，请填写备注！'));
        if (!$form->no_error) {
            JS::dialog(V('comment/add.user', [
                'form' => $form,
                'object' => $object,
                'comment' => $comment,
            ]), [
                'width' => 370, 
                'title' => '使用评价'
            ]);
            return;
        }

        $comment = O('eq_comment_user');
        $comment->equipment = $object->equipment;;
        $comment->source = $object;
        $comment->commentator = $me;
        $comment->user_attitude = $form['user_attitude'];
        $comment->user_proficiency = $form['user_proficiency'];
        $comment->user_cleanliness = $form['user_cleanliness'];
        $comment->test_importance = $form['test_importance'];
        $comment->test_dtstart = $object->dtstart;
        if ($object->name() == 'eq_sample') {
            $comment->user = $object->sender;
            $comment->test_dtend = $object->dtsubmit;
        } else {
            $comment->user = $object->user;
            $comment->test_dtend = $object->dtend;
        }
        $comment->test_purpose = $form['test_purpose'];
        $comment->test_method = $form['test_method'];
        $comment->test_result = $form['test_result'];
        $comment->test_fit = $form['test_fit'];
        $comment->test_understanding = $form['test_understanding'];
        $comment->test_remark = $form['test_remark'];

        if ($comment->save()) {
            //log
            Log::add(strtr('[eq_comment] %user_name[%user_id] 评价了使用记录[%object_id]', [
                '%user_name' => $me->name, 
                '%user_id' => $me->id, 
                '%object_id' => $object->id,
            ]), 'journal');
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('envmon', '评价成功!')); 
        }
        else Lab::message(Lab::MESSAGE_ERROR, I18N::T('envmon', '评价失败!')); 
        JS::refresh();
    }

    public function index_export_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
        $type = $form['type'];
        $tab = $form['tab'];
        if ($tab == 'incharge') {
            $columns = Config::get('comment.export_columns.incharge');
        } elseif ($tab == 'user') {
            $columns = Config::get('comment.export_columns.user');
        }

        if ($type == 'csv') {
            $title = I18N::T('labs','请选择要导出CSV的列');
        } elseif ($type == 'print') {
            $title = I18N::T('labs','请选择要打印的列');
        }
        
		JS::dialog(V('export_form',[
			'form_token' => $form_token,
			'columns' => $columns,
            'type' => $type,
            'tab' => $tab
		]),[
			'title' => $title
		]);

    }
    
    public function index_export_submit() {
    	$form = Input::form();
		$form_token = $form['form_token'];
		if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_comment', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
		$type = $form['type'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

		$file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

		if ('csv' == $type) {
			$pid = $this->_export_excel($form['selector'], $form, $file_name, $form['tab']);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
				'pid' => $pid
            ]), [
                'title' => I18N::T('eq_comment', '导出等待')
            ]);
		}
    }

    public function _export_excel($selector, $form, $file_name, $tab) {
        $me = L('ME');
        if ($tab == 'incharge') {
            $valid_columns = Config::get('comment.export_columns.incharge');
        } elseif ($tab == 'user') {
            $valid_columns = Config::get('comment.export_columns.user');
        }
		$visible_columns = $form['columns'] ? : $form['@columns'];

		foreach ($valid_columns as $p => $p_name ) {
			if ($visible_columns[$p] == 'null') {
				unset($valid_columns[$p]);
			}
		}

		if (isset($_SESSION[$me->id.'-export'])) {
			foreach ($_SESSION[$me->id.'-export'] as $old_pid => $old_form) {
				$new_valid_form = $form['columns'];

				unset($new_valid_form['form_token']);
				unset($new_valid_form['selector']);
				if ($old_form == $new_valid_form) {
					unset($_SESSION[$me->id.'-export'][$old_pid]);
					proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
				}
			}
		}

        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_'. $tab .' export ';
        $cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $valid_form = $form['columns'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id.'-export'][$pid] = $valid_form;
        return $pid;
	}
}