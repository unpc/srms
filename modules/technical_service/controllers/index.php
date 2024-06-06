<?php

class Index_Controller extends Layout_Controller
{

    function index()
    {
        URI::redirect('!technical_service/list');
    }

    function export_apply() {
        $form = Input::form();
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
		$type = $form['type'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $form['selector'];
		if ('csv' == $type) {
			$this->_apply_export_csv($selector, $form);
		}
		elseif ('print' == $type) {
			$this->_apply_export_print($selector, $form);
		}
	}

    function download($file_name, $suffix)
    {
        $file_name = FILE::fix_path($file_name);
        $path = Config::get('system.excel_path');
        $new_file_name = date('ymdHi', substr($file_name, 0, 10));
        if (Event::trigger('fudan_gao_mini.excel.name', $file_name)) {
            $new_file_name = Event::trigger('fudan_gao_mini.excel.name', $file_name);
        }

        if (file_exists($path . '/' . $file_name . '.' . $suffix)) {
            //Downloader::download($path.'/'.$file_name.'.'.$suffix, TRUE);
            header("Content-type: application/octet-stream");
            header("Accept-Ranges: bytes");
            header("Accept-Length: " . filesize($path . '/' . $file_name . '.' . $suffix));
            if (Browser::name() == 'firefox') {
                header("Content-Disposition: attachment; filename*=utf8'" . urlencode($new_file_name) . '.' . $suffix);
            } else {
                header("Content-Disposition: attachment; filename=" . urlencode($new_file_name) . '.' . $suffix);
            }
            ob_clean();

            echo file_get_contents($path . '/' . $file_name . '.' . $suffix);
            unlink($path . '/' . $file_name . '.' . $suffix);
            exit;
        }
    }

    private function _apply_export_print($selector, $form) {
        $valid_columns = Config::get('technical_service.export_columns.apply');
        $valid_columns = new ArrayIterator($valid_columns);
        $valid_columns = Event::trigger('service_apply.extra.export_columns', $valid_columns,$form) ?: $valid_columns;
		$visible_columns = Input::form('columns');

        $valid_columns = (array) $valid_columns;
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$applys = Q($selector);
		$this->layout = V('technical_service:incharge/apply_print', ['form' => $form, 'applys' => $applys,'valid_columns'=>$valid_columns]);
	}

}

class Index_AJAX_Controller extends AJAX_Controller
{

    public function index_download_click()
    {
        $form = Input::form();
        $path = Config::get('system.excel_path');
        $file_name = $form['file_name'];
        $ext = $form['ext'];
        if (file_exists($path . '/' . $file_name . '.' . $ext)) {
            JS::close_dialog();
            Output::$AJAX['res'] = $ext;
        } elseif (file_exists($path . '/' . $file_name . '.' . $ext)) {
            JS::close_dialog();
            Output::$AJAX['res'] = $ext;
        } else {
            Output::$AJAX['res'] = 'not found';
        }
    }

    function index_output_apply_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
        $columns = Config::get('technical_service.export_columns.apply');
        $columns = new ArrayIterator($columns);
        $columns = Event::trigger('service_apply.extra.export_columns', $columns,$form) ?: $columns;
		switch ($type) {
			case 'csv':
				$title = I18N::T('technical_serice', '请选择要导出Excel的列');
				break;
			case 'print':
				$title = I18N::T('technical_serice', '请选择要打印的列');
				break;
		}
		JS::dialog(V('technical_service:report/output_form', [
						  'form_token' => $form_token,
						  'columns' => $columns,
						  'type' => $type,
						  'object_name' => 'apply',
					]), [
						'title' => $title
					]);
	}

    function index_export_apply_submit() {
        $form = Input::form();
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '操作超时, 请重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
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

        $selector = $form['selector'];

		if ('csv' == $type) {
			$pid = $this->_apply_export_csv($selector, $form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
				'pid' => $pid
            ]), [
                'title' => I18N::T('calendars', '导出等待')
            ]);
		}
    }

    private function _apply_export_csv($selector, $form, $file_name) {
		$me = L('ME');
        $valid_columns = Config::get('technical_service.export_columns.apply');
        $valid_columns = new ArrayIterator($valid_columns);
        $valid_columns = Event::trigger('service_apply.extra.export_columns', $valid_columns,$form) ?: $valid_columns;
		$visible_columns = (array)$form['columns'];

		$valid_columns = (array)$valid_columns;
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
				unset($valid_columns[$p]);
			}
		}
		if (isset($_SESSION[$me->id.'-export'])) {
			foreach ($_SESSION[$me->id.'-export'] as $old_pid => $old_form) {
				$new_valid_form = $form['form'];

				unset($new_valid_form['form_token']);
				unset($new_valid_form['selector']);
				if ($old_form == $new_valid_form) {
					unset($_SESSION[$me->id.'-export'][$old_pid]);
					proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
				}
			}
		}
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_apply export ';
        $cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
        // exec($cmd, $output);
        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id.'-export'][$pid] = $valid_form;
        return $pid;
	}

    function index_output_apply_record_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
        $columns = Config::get('technical_service.export_columns.apply_record');
        $columns = new ArrayIterator($columns);
        $columns = Event::trigger('service_apply_record.extra.export_columns', $columns,$form) ?: $columns;
		switch ($type) {
			case 'csv':
				$title = I18N::T('technical_serice', '请选择要导出Excel的列');
				break;
			case 'print':
				$title = I18N::T('technical_serice', '请选择要打印的列');
				break;
		}
		JS::dialog(V('technical_service:report/output_form', [
						  'form_token' => $form_token,
						  'columns' => $columns,
						  'type' => $type,
						  'object_name' => 'apply_record',
					]), [
						'title' => $title
					]);
	}

    function index_export_apply_record_submit() {
        $form = Input::form();
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '操作超时, 请重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
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

        $selector = $form['selector'];

		if ('csv' == $type) {
			$pid = $this->_apply_record_export_csv($selector, $form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
				'pid' => $pid
            ]), [
                'title' => I18N::T('calendars', '导出等待')
            ]);
		}
    }

    private function _apply_record_export_csv($selector, $form, $file_name) {
		$me = L('ME');
        $valid_columns = Config::get('technical_service.export_columns.apply_record');
        $valid_columns = new ArrayIterator($valid_columns);
        $valid_columns = Event::trigger('service_apply_record.extra.export_columns', $valid_columns,$form) ?: $valid_columns;
		$visible_columns = (array)$form['columns'];

		$valid_columns = (array)$valid_columns;
		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
				unset($valid_columns[$p]);
			}
		}
		if (isset($_SESSION[$me->id.'-export'])) {
			foreach ($_SESSION[$me->id.'-export'] as $old_pid => $old_form) {
				$new_valid_form = $form['form'];

				unset($new_valid_form['form_token']);
				unset($new_valid_form['selector']);
				if ($old_form == $new_valid_form) {
					unset($_SESSION[$me->id.'-export'][$old_pid]);
					proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
				}
			}
		}
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_apply_record export ';
        $cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
        // exec($cmd, $output);
        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        $pid = intval($var['pid']) + 1;
        $valid_form = $form['form'];
        unset($valid_form['form_token']);
        unset($valid_form['selector']);
        $_SESSION[$me->id.'-export'][$pid] = $valid_form;
        return $pid;
	}

}