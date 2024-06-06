<?php

class Autocomplete_Controller extends AJAX_Controller {

	function task($id = 0) {
        if ($id) $task = O('tn_task', $id);
		$form = Input::form();
		$s = trim($form['s']);
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 100) return;
		$n = 5;
		if($start == 0) $n = 10;
		if ($s) {
			$s = Q::quote($form['s']);
            $selector = "tn_task[title*={$s}]";
            if ($task->id) $selector .= ":not($task)";
		}
		else {
			$selector = 'tn_task';
            if ($task->id) $selector .= ":not($task)";
		}

        $tasks = Q($selector);

		$tasks = $tasks->filter(':sort(mtime D)')->limit($start, $n);
		$tasks_count = $tasks->length();

		if ($start == 0 && !$tasks_count) {
			Output::$AJAX[] = [
				'html' => (string) V('autocomplete/special/empty'),
				'special' => TRUE
			];
		}
		else {
			foreach ($tasks as $task) {
				Output::$AJAX[] = [
					'html'=> (string) V('treenote:autocomplete/task', ['task'=>$task]),
					'alt'=>$task->id,
					'text'=>$task->title
				];
			}
//			$rest = $tasks->total_count() - $tasks_count;			
			if ($start == 95) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/rest'),
					'special' => TRUE
				];
			}
		}
	}

	function todo($uid=0) {
		$user = O('user', $uid);
		if (!L('ME')->is_allowed_to('列表用户任务', $user)) {
			return;
		}

		$s = trim(Input::form('s'));
		$st = trim(Input::form('st'));
		$start = 0;
		if ($st) {
			$start = $st;
		}
		if($start >= 5) return;
		
		if ($s) {
			$s = Q::quote($s);
			$tasks = Q("tn_task[title*={$s}][user={$user}]");
			$tasks_count = $tasks->total_count();
			if ($start == 0 && !$tasks_count) {
				Output::$AJAX[] = [
					'html' => (string) V('autocomplete/special/empty'),
					'special' => TRUE
				];
			}
			else {
				foreach ($tasks as $task) {
					Output::$AJAX[] = [
						'html'=>$task->title,
						'alt'=>$task->id,
						'text'=>$task->title
					];
				}
			}
		}
	}
}
