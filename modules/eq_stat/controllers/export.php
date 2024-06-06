<?php

class Export_Controller extends Layout_Controller {

	function index() {
		$form = Input::form();
		$type = $form['type'];
		// $form = (array)$_SESSION[Input::form('form_token')];

		$form_token = $form['form_token'];
        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;
        if ( $form['dtstart_check'] != 'on' ) {
	        unset( $form['dtstart'] );
        }
        if ( $form['dtend_check'] != 'on' ) {
	        unset( $form['dtend'] );
        }
		if ($type == 'print') {
			$this->_print($form);
		}
		elseif ($type == 'csv') {
			$this->_csv($form);
		}
		else {
			URI::redirect('error/401');
		}
	}

	private function _csv($form) {
		$valid_columns = EQ_Stat::get_export_columns();
		$visible_columns = $form['columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}
		$valid_columns_flip = array_flip($valid_columns);
        $group_root_tag = Tag_Model::root('group');
        if ($form['group_tag'] && $form['group_tag'] != $group_root_tag->id) {
        	$group_tag = O('tag_group', $form['group_tag']);
        	$alert_name = $group_tag->name;
        }

		$csv = new CSV('php://output', 'w', $alert_name);
        $csv_header = [];

        $stat_list = [];
        $stat_list['equipment'] = I18N::T('eq_stat', '仪器名称');
        $stat_list['eq_ref_no'] = I18N::T('eq_stat', '仪器编号');
        $stat_list['eq_cf_id'] = I18N::T('eq_stat', '仪器CF_ID');
        $stat_list['contact'] = I18N::T('eq_stat', '联系人');

        $stat_list = $stat_list + EQ_Stat::get_opts();
        foreach($stat_list as $key => $value) {
        	if (in_array($key, $valid_columns_flip)) {
            	$csv_header[] = I18N::T('eq_stat', $value);
        	}
        }

        $equipments = Q($form['selector']);

		if($form['dtstart_check'] && $form['dtend_check']) {
			$dtstart = $form['dtstart'];
			$dtend = $form['dtend'];
		}
		elseif($form['dtstart_check']) {
			$dtstart = $form['dtstart'];
		}
		elseif($form['dtend_check']) {
			$dtend = $form['dtend'];
		}

		$csv->write($csv_header);

        foreach($equipments as $equipment) {
			$project_values = Event::trigger('stat.equipment.project_statistic_values', $equipment, $form['dtstart'], $form['dtend']);
            $stat_content = [];

            foreach($stat_list as $key => $value) {
            	if (in_array($key, $valid_columns_flip)) {
                    $stat_opts = Config::get('eq_stat.stat_opts');
                    if ( strpos($key, 'project') !== false ) {
					  	$stat_content[] =	trim(V("eq_stat/export_value/$key", ['value' => $project_values[$key], 'type' => 'csv', ]));
						continue;
					}
                    if (array_key_exists($key,$stat_opts)) {
                       $stat_content[] = trim(V("eq_stat/export_value/$key", ['value'=> EQ_Stat::data_point($key, $equipment, $dtstart, $dtend), 'type'=> 'csv']));
                    }
                    else {
                       $stat_content[] = trim(V("eq_stat/export_value/$key", ['value'=> $equipment, 'type'=> 'csv']));
                    }
            	}
            }
			$csv->write($stat_content);
        }

        $csv->close();
	}

	private function _print($form) {
		$valid_columns = EQ_Stat::get_export_columns();
		$visible_columns = $form['columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

		$equipments = Q($form['selector']);
		$this->layout = V('eq_stat_print',[
			'equipments' => $equipments,
			'valid_columns' => $valid_columns,
			'form' => $form,

		]);

	}
}

class Export_AJAX_Controller extends AJAX_Controller {

    function index_list_export_submit() {

		$form = Input::form();
		$type = $form['type'];
		// $form = (array)$_SESSION[Input::form('form_token')];

		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_stat', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;
        if ( $form['dtstart_check'] != 'on' ) {
	        unset( $form['dtstart'] );
        }
        if ( $form['dtend_check'] != 'on' ) {
	        unset( $form['dtend'] );
        }
        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];
		if ($type == 'csv') {
			$pid = $this->_csv($form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
				'pid' => $pid
            ]), [
                'title' => I18N::T('eq_stat', '导出等待')
            ]);
		} else {
			URI::redirect('error/401');
		}
    }

	private function _csv($form, $file_name) {
		$me = L('ME');
		$valid_columns = EQ_Stat::get_export_columns();
		$visible_columns = $form['columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
				unset($valid_columns[$p]);
			}
		}
		$valid_columns_flip = array_flip($valid_columns);
        $group_root_tag = Tag_Model::root('group');
        if ($form['group_tag'] && $form['group_tag'] != $group_root_tag->id) {
        	$group_tag = O('tag_group', $form['group_tag']);
        	$alert_name = $group_tag->name;
        }

        $csv_header = [];

        $stat_list = [];
        $stat_list['equipment'] = I18N::T('eq_stat', '仪器名称');
        $stat_list['eq_ref_no'] = I18N::T('eq_stat', '仪器编号');
        $stat_list['eq_cf_id'] = I18N::T('eq_stat', '仪器CF_ID');
        $stat_list['contact'] = I18N::T('eq_stat', '联系人');

        $stat_list = $stat_list + EQ_Stat::get_opts();
        foreach($stat_list as $key => $value) {
        	if (in_array($key, $valid_columns_flip)) {
            	$csv_header[] = I18N::T('eq_stat', $value);
        	}
        }

        //$equipments = Q($form['selector']);

		if($form['dtstart_check'] && $form['dtend_check']) {
			$dtstart = $form['dtstart'];
			$dtend = $form['dtend'];
		}
		elseif($form['dtstart_check']) {
			$dtstart = $form['dtstart'];
		}
		elseif($form['dtend_check']) {
			$dtend = $form['dtend'];
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
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_eq_stat export ';
        $cmd .= "'".$form['selector']."' '".$form['dtstart']."' '".$form['dtend']."' '".$dtstart."' '".$dtend."' '".$file_name."' '".json_encode($valid_columns_flip, JSON_UNESCAPED_UNICODE)."' '".json_encode($stat_list, JSON_UNESCAPED_UNICODE)."' '".json_encode($csv_header, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
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
