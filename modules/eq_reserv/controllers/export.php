<?php
/*
NO.TASK282(guoping.zhang@2010.12.02）
仪器送样预约开发
*/
class Export_Controller extends Layout_Controller {

	function index() {
        $form = Input::form();
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
		$type = $form['type'];

        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $new_form + $old_form;
        $form['dtstart'] = $old_form['dtstart'] ? : $new_form['dtstart'];
        $form['dtend'] = $old_form['dtend'] ? : $new_form['dtend'];

        $calendar = O('calendar', $form['calendar_id']);
        $dtstart = $form['dtstart'] ? : $form['st'];
        $dtend = $form['dtend'] ? : $form['ed'];
        $selector = $form['selector'] ? : "cal_component[calendar={$calendar}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]";

		if ('csv' == $type) {
			$this->_export_csv($selector, $form);
		}
		elseif ('print' == $type) {
			$this->_export_print($selector, $form);
		}
	}

	private function _export_print($selector, $form) {
        $valid_columns = Config::get('calendar.export_columns.eq_reserv');

        $valid_columns = new ArrayIterator($valid_columns);
        Event::trigger('eq_reserv.extra.export_columns', $valid_columns);

        $valid_columns = (array)Event::trigger('calendar.extra.export_columns', $valid_columns, $form['form_token']) ?: $valid_columns;
        $visible_columns = (array)Input::form('columns');
		foreach ($valid_columns as $p => $p_name) {
            if (!isset($visible_columns[$p]) || $p < 0) {
				unset($valid_columns[$p]);
            }
		}
		$components = Q($selector);
        $calendar = O('calendar', $form['calendar_id']);
        if ($calendar->id) {
            $form = new ArrayIterator($form);
            $dtstart = $form['dtstart'] ? : $form['st'];
            $dtend = $form['dtend'] ? : $form['ed'];
            $new_components = Event::trigger('calendar.components.get', $calendar, $components, $dtstart, $dtend, 0, $form, $mode = 'list');
            $form = (array) $form;
            if ($new_components) {
                $components = $new_components;
            }
        }
		$this->layout = V('eq_reserv:reserv_print', ['form' => $form, 'components' => $components,'valid_columns'=>$valid_columns]);
	}
}

class Export_AJAX_Controller extends AJAX_Controller {

	function index_output_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
        $columns = Config::get('calendar.export_columns.eq_reserv');
        $columns = new ArrayIterator($columns);
        Event::trigger('eq_reserv.extra.export_columns', $columns);
		switch ($type) {
			case 'csv':
				$title = I18N::T('eq_reserv', '请选择要导出Excel的列');
				break;
			case 'print':
				$title = I18N::T('eq_reserv', '请选择要打印的列');
				break;
		}
		JS::dialog(V('eq_reserv:report/output_form', [
            'form_token' => $form_token,
            'columns' => (array) $columns,
            'type' => $type,
        ]), [
            'title' => $title
        ]);
	}

    function index_preview_click() {
        $form = Input::form();
        $reserv = O('eq_reserv', $form['sid']);
        if (!$reserv->id) return FALSE;

        Output::$AJAX['preview'] = (string) V('eq_reserv:preview', ['reserv' => $reserv]);
    }

	function index_reserv_export_submit() {
        $form = Input::form();
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '操作超时, 请重试!'));
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

        $selector = $form['selector'];

		if ('csv' == $type) {
			$pid = $this->_export_csv($selector, $form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
				'pid' => $pid
            ]), [
                'title' => I18N::T('calendars', '导出等待')
            ]);
		}
    }

	private function _export_csv($selector, $form, $file_name) {
        $me = L('ME');
        $valid_columns = Config::get('calendar.export_columns.eq_reserv');
        $valid_columns = new ArrayIterator($valid_columns);
        Event::trigger('eq_reserv.extra.export_columns', $valid_columns);
		$visible_columns = (array)$form['columns'];
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
		$samplesp= Q($selector);
		//if ($samples->total_count()) {
            putenv('Q_ROOT_PATH=' . ROOT_PATH);
            $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_eq_reserv export ';
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
        //}
	}
}
