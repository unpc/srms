<?php

class Report_Controller extends Layout_Controller {

	function index() {
		$form_token = Input::form('form_token');
        $old_form = (array) $_SESSION[$form_token];
        if (!$old_form) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }

        $new_form = Input::form();

        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

		$type = Input::form('type');

		if ($type == 'print') {
			$this->_print($form);
		}
		elseif ($type == 'csv') {
			if (count($new_form['export_item_name']) > 0) {
				foreach($new_form['export_item_name'] as $k => $v) {
					$form['columns'][] = 'extraitem|'.$v.'|'.$new_form['searchtype'][$k].'|'.$new_form['searchoption'][$k].'|'.$new_form['export_item_value'][$k];
				}
			}
			$this->_csv($form);
		}
		else {
			URI::redirect('error/401');
		}
	}

	private function _print($form){

        $valid_columns = Config::get('eq_charge.export_columns.eq_charge');
		$valid_columns = new ArrayIterator($valid_columns);
        $valid_columns = Event::trigger('eq_charge_export.cloumns', $valid_columns, $form['oname'], $form['oid']) ?: $valid_columns;
		$visible_columns = $form['columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

		$charges = Q($form['selector']);
		$max_print_count = Config::get('eq_charge.print_max', 500);

        $form_token = $form['form_token'];
        $new_form = $form;
        unset($new_form['columns']);
        $new_form['columns'] = $valid_columns;
        $_SESSION[$form_token] = $new_form;

        $dtstart = $new_form['form']['dtstart'] ?: 0;
        $dtend = $new_form['form']['dtend'] ?: 0;
        $data = $dtstart ? Date::format($dtstart, 'Y/m/d') : H('最初');
        $data .= ' ~ ';
        $data .= $dtend ? Date::format($dtend, 'Y/m/d') : H('现在');

       	$obj = O($new_form['oname'], $new_form['oid']);


        if ($charges->total_count() > $max_print_count) {
            $csv_link = I18N::T('eq_charge', '导出Excel');
            $return_url = I18N::T('eq_charge', '搜索条件');
            $title = I18N::T('eq_charge', '%name费用汇总表%data', [
				'%name' => H($obj->name),
				'%data' => $data
			]);

            $this->layout = V('eq_charge:eq_charges_excessive', [
				'title' => $title,
				'csv_link' => $csv_link,
				'return_url' => $return_url,
				'max_print_count' => $max_print_count,
            ]);
        }
        else {
            $this->layout = V('eq_charge:report/print');
            $this->layout->charges = $charges;
            $this->layout->dtstart = $new_form['form']['dtstart'] ?: null;
            $this->layout->dtend = $new_form['form']['dtend'] ?: null;
            $this->layout->columns = $valid_columns;
            $this->layout->data = $data;
            $this->layout->obj = $obj;

            /* 记录日志 */
            $me = L('ME');

            Log::add(strtr('[eq_charge] %user_name[%user_id]打印了仪器的收费记录', [
				'%user_name' => $me->name,
				'%user_id' => $me->id,
			]), 'journal');
        }
	}

	private function _csv($form) {
		$valid_columns = Config::get('eq_charge.export_columns.eq_charge');
		$visible_columns = $form['columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

		//添加导出项列
		$extra_columns = [];
		foreach ($visible_columns as $p => $p_name) {
			if (strpos($p_name, 'xtraitem|')) {
				$extra_columns[] = $p_name;
				$valid_columns[] = explode('|', $p_name)[1];
			}
		}
		
		$charges = Q($form['selector']);
		$dtstart = $form['dtstart'];
		$dtend = $form['dtend'];
		$csv = new CSV('php://output', 'w');
		/* 记录日志 */
		$me = L('ME');
		Log::add(strtr('[eq_charge] %user_name[%user_id]以CSV导出了仪器的收费记录', [
			'%user_name' => $me->name,
			'%user_id' => $me->id,
			]), 'journal');
		$csv->write(I18N::T('eq_charge',$valid_columns));
		if ($charges->total_count() > 0) {

			$start = 0;
			$per_page = 100;

			while (1) {
				$pp_cs = $charges->limit($start, $per_page);
				if ($pp_cs->length() == 0) break;
				/*
				NO.BUG#099
				2010.11.05
				张国平
				导出成CSV，数字不能包含￥/$标志
				*/
				foreach ($pp_cs as $c) {
					$user = $c->user;
					$equipment = $c->equipment;
					$data = [];

					if (array_key_exists('equipment', $valid_columns)) {
						$data[] = H($equipment->name);
					}
					if (array_key_exists('eq_ref_no', $valid_columns)) {
						$data[] = $equipment->ref_no;
					}
					if (array_key_exists('eq_cf_id', $valid_columns)) {
						$data[] = $equipment->id;
					}
					if (array_key_exists('eq_group', $valid_columns)) {
						$data[] = H($equipment->group->name);
					}
					if (array_key_exists('incharge', $valid_columns)) {
							$users = Q("{$equipment} user.contact")->to_assoc('id', 'name');
							$users = join(', ', $users);
							$data[] = H($users);
					}
					if (array_key_exists('user', $valid_columns)) {
						$data[] = H($user->name);
					}
					if (array_key_exists('lab', $valid_columns)) {
						$data[] = H($charge->lab->name);
					}
					if (array_key_exists('user_group', $valid_columns)) {
						$data[] = H($user->group->name);
					}
					if (array_key_exists('charge_ref_no', $valid_columns)) {
						$data[] = Number::fill($c->transaction->id, 6);
					}
					if (array_key_exists('date', $valid_columns)) {
						$data[] = Date::format($c->ctime, 'Y/m/d H:i');
					}
					if (array_key_exists('samples', $valid_columns)) {
                        $source = $c->source;
                        if ($source->id) {
	                        switch($source->name()){
	                            case 'eq_sample':
							        $data[] = (int)max(1, $source->count);
	                                break;
	                            case 'eq_record':
							        $data[] = $source->samples;
	                                break;
	                            default:
							        $data[] = '--';
	                                break;
	                        }
                        }
                        else {
                        	$data[] = '--';
                        }
					}
					if (array_key_exists('amount', $valid_columns)) {
						$data[] = $c->amount;
					}
					if (array_key_exists('type', $valid_columns)) {
                        if ($c->source->id) {
							switch($c->source->name()) {
								case 'eq_sample':
									$data[] = I18N::HT('eq_charge', '送样收费');
									break;
								case 'eq_reserv':
									$data[] = I18N::HT('eq_charge', '预约收费');
									break;
								default:
									$data[] = I18N::HT('eq_charge', '使用收费');
							}
                        }
                        else {
                            $data[] = NULL;
                        }
					}
					if (array_key_exists('charge_time', $valid_columns)) {
						$data[] = $c->source->dtstart&&$c->source->dtend ? date('Y-m-d H:i:s', $c->source->dtstart) . ' - ' . date('Y-m-d H:i:s', $c->source->dtend) : '--';
					}

                    $new_data = Event::trigger('eq_charge.export_columns', $c, $valid_columns, $data);
                    if ($new_data) $data = $new_data;

					foreach($extra_columns as $extra_column) {
						$data[] = EQ_Charge_Search::get_export_value($extra_column, $c);
					}

					$csv->write($data);
				}

				$start += $per_page;
			}
		}

		$csv->close();
	}

}

class Report_AJAX_Controller extends AJAX_Controller {
	//仪器使用收费新打印功能
	function index_output_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$columns = Config::get('eq_charge.export_columns.eq_charge');
		$columns = new ArrayIterator($columns);
        $columns = Event::trigger('eq_charge_export.cloumns', $columns, $form['oname'], $form['oid']) ?: $columns;

        if (!$_SESSION[$form_token]) {
            JS::alert(I18N::T('eq_charge', '操作超时, 请刷新页面后重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
            return FALSE;
        }

		if ($type == 'csv') {
			$title = I18N::T('eq_charge', '请选择要导出Excel的列');
		}
		else {
			$title = I18N::T('eq_charge', '请选择要打印的列');
		}
		JS::dialog(V('eq_charge:report/output_form', [
						'type' => $type,
						'form_token' => $form_token,
						'columns' => $columns,
						'oid' => $form['oid'],
						'oname' => $form['oname']
					]), [
						'title' => $title
					]);
	}

	function index_charge_export_submit() {
		$form = Input::form();
		$form_token = Input::form('form_token');
		if ( !$_SESSION[$form_token] ) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
			URI::redirect($_SESSION['system.current_layout_url']);
		}
		$flag = true;
        $extraitems = $form['extraitems'];
        $searchtypes = $form['searchtypes'];
        $extravalues = $form['extravalues'];
        if ($extraitems) foreach($extraitems as $v) {
            if ($v == '') {
                $flag = false;
                break;
            }
        }

        if(!$flag) {
            JS::alert(I18N::T('eq_charge', '导出项名称 不能为空'));
        }
        else {
            for($i = 0; $i < count($searchtypes); $i++) {
                if($searchtypes[$i] == EQ_Charge_Search::SEARCH_SAMPLE) {
                    if(!is_numeric($extravalues[$i]) || ((float)$extravalues[$i] != (int)$extravalues[$i])) {
                        $flag = false;
                        JS::alert(I18N::T('eq_charge', '样品数应为整数'));
                        break;
                    }
                }
            }
        }

        Output::$AJAX['result'] = $flag;

        $old_form = (array) $_SESSION[$form_token];
        if (!$old_form) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }

        $new_form = Input::form();

        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

		$type = Input::form('type');

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

		if ($type == 'csv') {
			if (count($new_form['export_item_name']) > 0) {
				foreach($new_form['export_item_name'] as $k => $v) {
					$form['columns'][] = 'extraitem|'.$v.'|'.$new_form['searchtype'][$k].'|'.$new_form['searchoption'][$k].'|'.$new_form['export_item_value'][$k];
				}
			}
			$pid = $this->_csv($form, $file_name);
            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
				'pid' => $pid
            ]), [
                'title' => I18N::T('calendars', '导出等待')
            ]);
		} else {
			URI::redirect('error/401');
		}
	}

	private function _csv($form, $file_name) {
        $valid_columns = Config::get('eq_charge.export_columns.eq_charge');
		$valid_columns = new ArrayIterator($valid_columns);
        $valid_columns = Event::trigger('eq_charge_export.cloumns', $valid_columns, $form['oname'], $form['oid']) ?: $valid_columns;
		$visible_columns = $form['columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
				unset($valid_columns[$p]);
			}
		}

		//添加导出项列
		$extra_columns = [];
		foreach ($visible_columns as $p => $p_name) {
			if (strpos($p_name, 'xtraitem|')) {
				$extra_columns[] = $p_name;
				$valid_columns[] = explode('|', $p_name)[1];
			}
		}

		$charges = Q($form['selector']);
		$dtstart = $form['dtstart'];
		$dtend = $form['dtend'];
		/* 记录日志 */
		$me = L('ME');
		Log::add(strtr('[eq_charge] %user_name[%user_id]以CSV导出了仪器的收费记录', [
			'%user_name' => $me->name,
			'%user_id' => $me->id,
			]), 'journal');

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
			
		//if ($charges->total_count() > 0) {
            putenv('Q_ROOT_PATH=' . ROOT_PATH);
            $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_charge export ';
            $cmd .= "'".$form['selector']."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' '".$file_name."' '".json_encode($extra_columns, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
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
