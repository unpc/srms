<?php

class Transactions_Controller extends Base_Controller {
	/*
	NO.TASK#300(guoping.zhang@2010.12.10)
	财务部门收支明细
	*/
	/*
	function index($id=0) {
		$me = L('ME');
		if (!$me->is_allowed_to('列表收支明细', 'billing_department')) {
			URI::redirect('error/401');
		}
		$form = Lab::form(function(&$old_form, &$form){
			if (isset($form['date_filter'])) {
				if (!$form['dtstart_check']) {
					unset($old_form['dtstart_check']);
				}
				if (!$form['dtend_check']) {
					unset($old_form['dtend_check']);
				}
				else {
                    $form['dtend'] = Date::get_day_end($form['dtend']);
				}
				unset($form['date_filter']);
			}
		});

		if ($GLOBALS['preload']['billing.single_department']) {
		#ifdef (billing.single_department)
			$department = Billing_Department::get();
			$selector = "billing_account[department={$department}]";
		#endif
		}
		else {
		#ifndef (billing.single_department)
			$selector = 'billing_account';

			if ($form['department_id']) {
				$department_id = Q::quote($form['department_id']);
				$selector .= "[department_id=$department_id]";
			}
		#endif
		}

		$account_suf = 'billing_transaction[income!=0|outcome!=0]';
		$account_selector = $selector;
		$account_pre = array();

		//当前用户只可看到下属机构的lab的收支明细
		if (!$me->is_allowed_to('列表收支明细', 'billing_department')) {
			$selector = "{$me}<group tag[parent] lab {$selector}";
			$account_pre[] = "{$me}<group tag[parent] lab";
		}

		$selector .= '<account billing_transaction[income!=0|outcome!=0]';

		//按时间搜索
		if($form['dtstart_check']){
			$dtstart = Q::quote($form['dtstart']);
			$selector .= "[ctime>=$dtstart]";
			$account_suf .= "[ctime>=$dtstart]";
		}

		if($form['dtend_check']){
			$dtend = Q::quote($form['dtend']);
			$selector .= "[ctime>0][ctime<=$dtend]";
			$account_suf .= "[ctime>0][ctime<=$dtend]";
		}

		if(!$form['dtstart_check'] && !$form['dtend_check']) {
			$dtend_date = getdate(time());
			$form['dtend'] = mktime(23, 59, 59, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
			$form['dtstart'] = $form['dtend'] - 2592000;
		}
		$transactions = Q($selector . ':sort(ctime D, id D)');

		$account_suf .= '<account';
		$account_pre[] = $account_suf;
		if (count($account_pre)) {
			$account_selector = '('.implode(',', $account_pre).') '.$account_selector;
		}

		$account_count = Q($account_selector)->total_count();
		$total_income = $transactions->sum('income');
		$total_outcome = $transactions->sum('outcome');
		$total_balance = $total_income - $total_outcome;

		$pagination = Lab::pagination($transactions, (int)$form['st'], 20);
		$content = V('transactions', array(
						 'transactions'=>$transactions,
						 'pagination'=>$pagination,
						 'form'=>$form,
						 'account_count'=>$account_count,
						 'total_income'=>$total_income,
						 'total_outcome'=>$total_outcome,
						 'total_balance'=>$total_balance,
						 ));
		$this->layout->body->primary_tabs
			->select('transactions')
			->set('content', $content);
	}
	*/
    function index() {
        URI::redirect('error/404');
    }

	function transactions_print() {
		$form_token = Input::form('form_token');
		$valid_columns = Config::get('billing.export_columns.transactions');
		$visible_columns = Input::form('columns');

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

		$form = $_SESSION[$form_token];
		$selector = $form['Q_query'];

		$transactions = Q($selector . ':sort(ctime D, id D)');

		$max_print_count = Config::get('print.max.print_count', 500);

		if($transactions->total_count() > $max_print_count){
			$form['@columns'] = $valid_columns;
			$_SESSION[$form_token] = $form;

			$dept = Input::form('dept');
			$department = O('billing_department', $dept);
			$department_url = $department->url('transactions', NULL, NULL, 'view');
			$csv_link = I18N::T('billing', '导出Excel');
			$return_url = I18N::T('billing', '搜索条件');
			$title = I18N::T('billing', '财务明细统计报表');

			$this->layout = V('billing:transactions_excessive', ['title' => $title,
					'csv_link' => $csv_link,
					'return_url' => $return_url,
					'max_print_count' => $max_print_count,
					]);
		}
		else{
			$this->layout = V('billing:transactions_print', ['form'=>$form, 'transactions'=>$transactions,'valid_columns'=>$valid_columns]);
		}
	}

	function transactions_csv() {
		$form = Input::form();
		$form_token = $form['form_token'];
        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $form['Q_query'];
		$transactions = Q($selector . ':sort(ctime D, id D)');

		$valid_columns = Config::get('billing.export_columns.transactions');

		$visible_columns = $form['columns'] ?: $form['@columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

        uasort($valid_columns, function($a, $b) {
            $aw = (int) isset($a['weight']) ? $a['weight'] : 0;
            $bw = (int) isset($b['weight']) ? $b['weight'] : 0;

            if ($aw == $bw) {
                return 0;
            }
            elseif ($aw < $bw) {
                return -1;
            }
            else {
                return 1;
            }
        });

        $valid_columns_header = [];
        foreach($valid_columns as $key => $value) {
            $valid_columns_header[] = $value['title'];
        }

		$csv = new CSV('php://output', 'w');
		$csv->write(I18N::T('billing',$valid_columns_header));
		if ($transactions->total_count() > 0) {

			$start = 0;
			$per_page = 100;
			while (1) {
				$pp_trans = $transactions->limit($start, $per_page);
				if ($pp_trans->length() == 0) break;

				foreach ($pp_trans as $t) {

                    $data = [];
                    foreach($valid_columns as $key => $tmp) {
                        $data[] = (string) V("billing:transactions_output/export/$key", ['transaction'=> $t]);
                    }

					$csv->write($data);
				}
				$start += $per_page;
			}
		}

		$csv->close();

	}
}

class Transactions_AJAX_Controller extends AJAX_Controller {

	function index_export_click() {
		$form = Input::form();
		$form_token = $form['form_token'];
		$type = $form['type'];
		$dept = $form['dept'];
		$lab_id = $form['lab_id'];

		$columns = Config::get('billing.export_columns.transactions');
		switch ($type) {
			case 'csv':
				$title = I18N::T('billing', '请选择要导出Excel的列');
				$query = $_SESSION[$form_token]['Q_query'];
                $total_count = Q($query)->total_count();
                if($total_count > 8000){
           		$description = I18N::T('billing', '数据量过多, 可能导致导出失败, 请缩小搜索范围!');
           		}
				break;
			case 'print':
				$title = I18N::T('billing', '请选择要打印的列');
				break;
		}
		JS::dialog(V('export_form', [
						'type' => $type,
						'form_token' => $form_token,
						'dept' => $dept,
						'columns' => $columns,
						'lab_id' => $lab_id,
						'description' =>$description,
					]),[
						'title' => $title
					]);
	}

    function index_transaction_type_change() {
        $form = Input::form();
        $transaction_type = $form['transaction_type'];

        switch($transaction_type) {
            case Billing_Transaction::FIN_TYPE_IN :
                $view_path = 'billing:transactions_table/filters/in_type';
            break;
            case Billing_Transaction::FIN_TYPE_OUT :
                $view_path = 'billing:transactions_table/filters/out_type';
            break;
            default :
        }

        if ($view_path) Output::$AJAX['view_data'] = (string) V($view_path, ['type'=> $form['sub_transaction_type']]);
    }

    function index_billing_transaction_export_submit() {
		$me = L('ME');
		$form = Input::form();
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
            URI::redirect($_SESSION['system.current_layout_url']);
        }
        $old_form = (array) $_SESSION[$form_token];
        $new_form = (array) $form;
        if (isset($new_form['columns'])) {
            unset($old_form['columns']);
        }

        $form = $_SESSION[$form_token] = $new_form + $old_form;

        $selector = $form['Q_query'];
		$transactions = Q($selector . ':sort(ctime D, id D)');

		$valid_columns = Config::get('billing.export_columns.transactions');

		$visible_columns = $form['columns'] ?: $form['@columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
				unset($valid_columns[$p]);
			}
		}

        uasort($valid_columns, function($a, $b) {
            $aw = (int) isset($a['weight']) ? $a['weight'] : 0;
            $bw = (int) isset($b['weight']) ? $b['weight'] : 0;

            if ($aw == $bw) {
                return 0;
            }
            elseif ($aw < $bw) {
                return -1;
            }
            else {
                return 1;
            }
        });

        $valid_columns_header = [];
        foreach($valid_columns as $key => $value) {
            $valid_columns_header[] = $value['title'];
        }

		if (isset($_SESSION[$me->id.'-export'])) {
			foreach ($_SESSION[$me->id.'-export'] as $old_pid => $old_form) {
				$new_valid_form = $form['form'];

				unset($new_valid_form['form_token']);
				unset($new_valid_form['Q_query']);
				if ($old_form == $new_valid_form) {
					unset($_SESSION[$me->id.'-export'][$old_pid]);
					proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
				}
			}
		}

		//if ($transactions->total_count() > 0) {

            $file_name_time = microtime(TRUE);
            $file_name_arr = explode('.', $file_name_time);
            $file_name = $file_name_arr[0].$file_name_arr[1];

            putenv('Q_ROOT_PATH=' . ROOT_PATH);
            $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_billing_transaction export ';
            $cmd .= "'".$selector."' '".$file_name."' '".$form['object_name']."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' '".json_encode($visible_columns,
            JSON_UNESCAPED_UNICODE)."' '".json_encode($valid_columns_header, JSON_UNESCAPED_UNICODE)."' '".$me->id. "' >/dev/null 2>&1 &";
            // exec($cmd, $output);
			$process = proc_open($cmd, [], $pipes);
			$var = proc_get_status($process);
			proc_close($process);
			$pid = intval($var['pid']) + 1;
			$valid_form = $form['form'];
			unset($valid_form['form_token']);
			unset($valid_form['Q_query']);
			$_SESSION[$me->id.'-export'][$pid] = $valid_form;

            JS::dialog(V('export_wait', [
                'file_name' => $file_name,
				'pid' => $pid
            ]), [
                'title' => I18N::T('billing', '导出等待')
            ]);
		//}
    }
}
