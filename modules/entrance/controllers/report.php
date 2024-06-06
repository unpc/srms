<?php

class Report_Controller extends Layout_Controller {

	private function _fetch_records(&$form) {
		$door = O('door',$form['door_id']);
		$form = $_SESSION[$form['form_token']];

		$pre_selector = [];
		if($form['name'] || $form['location1'] || $form['location2']) {
			$door_selector = 'door';

			if($form['name']) {
				$name = Q::quote($form['name']);
				$door_selector .= "[name*=$name]";
			}

			if($form['location1']) {
				$location1 = Q::quote($form['location1']);
				$door_selector .= "[location1*=$location1]";
			}

			if($form['location2']) {
				$location2 = Q::quote($form['location2']);
				$door_selector .= "[location2*=$location2]";
			}
			$pre_selector[] = $door_selector;
		}

		if($form['user']) {
			$user = Q::quote($form['user']);
			$pre_selector[] = "user[name*=$user]";
		}

		if($form['lab']) {
			$lab = Q::quote($form['lab']);
			$pre_selector[] = "lab[name*=$lab] user";
		}

		if(count($pre_selector) > 0) {
			$selector = '('.implode(',', $pre_selector).') ';
		}

		$selector .= 'dc_record';

		if($door->id) {
			$selector .= "[door=$door]";
		}

		if($form['dtstart_check']){
			$dtstart = Q::quote($form['dtstart']);
			$selector .= "[time>=$dtstart]";
		}

		if($form['dtend_check']){
			$dtend = Q::quote($form['dtend']);
			$selector .= "[time>0][time<=$dtend]";
		}

		if($form['direction'] || $form['direction'] === '0') {
			$direction = Q::quote($form['direction']);
			$selector .= "[direction=$direction]";
		}

		switch ($form['attendance']) {
		case DC_Record_Model::FILTER_EARLIEST :
			$selector .= ':daymin(time|door_id,user_id)';
			break;
		case DC_Record_Model::FILTER_LATEST :
			$selector .= ':daymax(time|door_id,user_id)';
			break;
		}


		/*
			BUG #433 (Cheng.liu@2011.03.24)
			打印和导出CSV时没有按照给定条件进行排序计算
		*/
		$sort_by = $form['sort'] ?: 'time';
		$sort_asc = $form['sort_asc'];
		$sort_flag = $sort_asc ? 'A':'D';

		$selector .= ":sort({$sort_by} {$sort_flag})";

		return Q($selector);
	}

	function index() {
		URI::redirect('error/404');
	}

	function printing() {

		$valid_columns = Config::get('entrance.export_columns.entrance');
        $visible_columns = Input::form('columns');
		$door = O('door', Input::form('door'));

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

		$me = L('ME');
		if (!$me->is_allowed_to('导出记录', $door)) {
			URI::redirect('error/401');
		}
        $form_token = Input::form('form_token');
		$form = $_SESSION[$form_token];
        $selector = $form['selector'];
		$records = Q($selector);
		$this->layout = V('entrance:report/print',['valid_columns'=>$valid_columns, 'form' => $form]);
		$this->layout->records = $records;
		$this->layout->dtstart = $form['dtstart_check'] ? $form['dtstart'] : null;
		$this->layout->dtend = $form['dtend_check'] ? $form['dtend'] : null;
		/* 记录日志 */
		Log::add(strtr('[entrance] %user_name[%user_id]打印了门禁记录', [
					'%user_name' => $me->name,
					'%user_id' => $me->id,

		]), 'journal');
	}

	function csv() {
		$me = L('ME');
		$form = Input::form();
		$door = O('door', $form['door']);
		if (!$me->is_allowed_to('导出记录', $door)) {
			URI::redirect('error/401');
		}
		$form_token = $form['form_token'];

		$old_form = (array) $_SESSION[$form_token];
		$new_form = (array) $form;
		if (isset($new_form['columns'])) {
		    unset($old_form['columns']);
		}

        $form = $_SESSION[$form_token] = $new_form + $old_form;

		$valid_columns = Config::get('entrance.export_columns.entrance');
		$visible_columns = $form['columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p])) {
				unset($valid_columns[$p]);
			}
		}

		$selector = $form['selector'];
		$records = Q($selector);
		$csv = new CSV('php://output','w');
		/* 记录日志 */
		Log::add(strtr('[entrance] %user_name[%user_id]以CSV导出了门禁记录', [
					'%user_name' => $me->name,
					'%user_id' => $me->id,

		]), 'journal');

		$csv->write(I18N::T('entrance',$valid_columns));

		if ($records->total_count() > 0) {

			$start = 0;
			$per_page = 100;

			while(1) {
				$pp_records = $records->limit($start,$per_page);
				if($pp_records->length() == 0 ) break;

				foreach($pp_records as $record) {
					$data = [];
					 foreach ($visible_columns as $key => $value) {
						switch ($key) {
						case 'name':
							$data[] = H($record->door->name);
							break;
						case 'location':
							$data[] = H($record->door->location1.$record->door->location2);
							break;
						case 'user':
							$data[] = H($record->user->name);
							break;
						case 'lab':
							$labs = Q("{$record->user} lab")->to_assoc('id', 'name');
							$data[] = H(join(',', $labs));
							break;
						case 'date':
							$data[] = H(date('Y/m/d H:i:s',$record->time));
							break;
						case 'direction':
							$data[] = H(DC_Record_Model::$direction[$record->direction]);
							break;
						}
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

    function index_entrance_export_submit() {
		$me = L('ME');
		$form = Input::form();
		$door = O('door', $form['door']);
		if (!$me->is_allowed_to('导出记录', $door)) {
			JS::redirect('error/401');
		}
		$form_token = $form['form_token'];
        if ( !$_SESSION[$form_token] ) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('equipments', '操作超时, 请重试!'));
            JS::redirect($_SESSION['system.current_layout_url']);
        }

		$old_form = (array) $_SESSION[$form_token];
		$new_form = (array) $form;
		if (isset($new_form['columns'])) {
		    unset($old_form['columns']);
		}

        $form = $_SESSION[$form_token] = $new_form + $old_form;

		$valid_columns = Config::get('entrance.export_columns.entrance');
		$visible_columns = $form['columns'];

		foreach ($valid_columns as $p => $p_name) {
			if (!isset($visible_columns[$p]) || $visible_columns[$p] == 'null') {
				unset($valid_columns[$p]);
			}
		}

		$selector = $form['selector'];
		$records = Q($selector);
		/* 记录日志 */
		Log::add(strtr('[entrance] %user_name[%user_id]以CSV导出了门禁记录', [
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

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

		//if ($records->total_count() > 0) {
            putenv('Q_ROOT_PATH=' . ROOT_PATH);
            $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_entrance export ';
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
		//}

        JS::dialog(V('export_wait', [
            'file_name' => $file_name,
			'pid' => $pid
        ]), [
            'title' => I18N::T('calendars', '导出等待')
        ]);
    }
}
