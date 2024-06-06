<?php

class Index_Controller extends Base_Controller {

	function index() {
		URI::redirect('!treenote/work');
	}
}

class Index_AJAX_Controller extends AJAX_Controller {

	function index_project_complete_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];
		$user = O('user', $form['user_id']);
		$project = O('tn_project', $id);
		if ($project->id && !$project->is_complete && $me->is_allowed_to('完成', $project)) {
			if (JS::confirm(I18N::T('treenote', '此操作会同时完成所有后代任务，并锁定任务下所有记录。'))) {
				$project->complete($user);

				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]完成了项目%project_title[%project_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=> L('ME')->id,
                    '%project_title'=> $project->title,
                    '%project_id'=> $project->id
                ]), 'journal');

				JS::refresh();
			}
		}
	}

	function index_task_complete_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];
		$task = O('tn_task', $id);
		if ($task->id && !$task->is_complete && $me->is_allowed_to('评审', $task)) {
			if (JS::confirm(I18N::T('treenote', '此操作会同时完成所有后代任务，并锁定任务下所有记录。'))) {	
				$user = O('user', $form['user_id']);
				$task->complete($user);

				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]完成了任务%task_title[%task_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=> L('ME')->id,
                    '%task_title'=> $task->title,
                    '%task_id'=> $task->id
                ]), 'journal');

				JS::refresh();
			}
		}
	}

	function index_project_reactivate_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];
		$project = O('tn_project', $id);
		if ($project->id && $project->is_complete && $me->is_allowed_to('激活', $project)) {
			if (JS::confirm(I18N::T('treenote', '此操作会同时激活所有后代任务'))) {
				$project->reactivate();
				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]激活了项目%project_title[%project_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=> L('ME')->id,
                    '%project_title'=> $project->title,
                    '%project_id'=> $project->id
                ]), 'journal');

				JS::refresh();
			}
		}
	}

	function index_task_reactivate_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];
		$task = O('tn_task', $id);
		if ($task->id && $task->is_complete && $me->is_allowed_to('评审', $task)) {
			if (JS::confirm(I18N::T('treenote', '此操作会同时激活所有后代任务'))) {
				$task->reactivate();

				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]激活了任务%task_title[%task_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=> L('ME')->id,
                    '%task_title'=> $task->title,
                    '%task_id'=> $task->id
                ]), 'journal');

				JS::refresh();
			}
		}		
	}

	function index_project_lock_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];
		$project = O('tn_project', $id);
		if ($project->id && !$project->is_complete && $me->is_allowed_to('锁定', $project)) {
			if (JS::confirm(I18N::T('treenote', '此操作会同时锁定所有后代任务'))) {
				$locker_id = $form['locker_id'];
				$locker = O('user', $locker_id);
				$project->lock($locker);

				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]锁定了项目%project_title[%project_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=> L('ME')->id,
                    '%project_title'=> $project->title,
                    '%project_id'=> $project->id
                ]), 'journal');

				JS::refresh();
			}
		}
	}

	function index_task_lock_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];	
		$task = O('tn_task', $id);
		if ($task->id && !$task->is_locked && $me->is_allowed_to('锁定', $task)) {
			if (JS::confirm(I18N::T('treenote', '此操作会同时锁定所有后代任务'))) {
				$locker_id = $form['locker_id'];
				$locker = O('user', $locker_id);
				$task->lock($locker);

				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]锁定了任务%task_title[%task_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=> L('ME')->id,
                    '%task_title'=> $task->title,
                    '%task_id'=> $task->id
                ]), 'journal');

				JS::refresh();			
			}
		}
	}

	function index_note_lock_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];
		$note = O('tn_note', $id);
		if ($note->id && !$note->is_locked && $me->is_allowed_to('锁定', $note)) {
			if (JS::confirm(I18N::T('treenote', '您确认要执行锁定记录操作吗?'))) {
				$note->lock();
				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]锁定了记录%note_title[%note_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=>  L('ME')->id,
                    '%note_title'=> $note->title,
                    '%note_id'=> $note->id
                ]), 'journal');

				JS::refresh();					
			}
		}
	}

	function index_clean_lock_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];
		$task = O('tn_task', $id);
		if ($task->id && $task->is_locked && $me->is_allowed_to('清除锁定',$task)) {
			if (JS::confirm(I18N::T('treenote', '此操作会同时解锁所有后代任务'))) {
				$task->clean_lock();
				JS::refresh();
			}
		}
	}

	function index_project_unlock_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];
		$project = O('tn_project', $id);
		if ($me->is_allowed_to('解锁', $project) && $project->id && $project->is_locked){
			if (JS::confirm(I18N::T('treenote', '此操作会同时解除您对所有后代任务的锁定'))) {
				$locker_id = $form['locker_id'];
				$locker = O('user', $locker_id);
				$project->unlock($locker);

				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]解锁了项目%project_title[%project_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=> L('ME')->id,
                    '%project_title'=> $project->title,
                    '%project_id'=> $project->id
                ]), 'journal');

				JS::refresh();
			}
		}
	}
	function index_task_unlock_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];
		$task = O('tn_task', $id);
		if ($task->id && $task->is_locked && $me->is_allowed_to('解锁', $task)) {
			if (JS::confirm(I18N::T('treenote', '此操作会同时解除您对所有后代任务的锁定'))) {
				$locker_id = $form['locker_id'];
				$locker = O('user', $locker_id);
				$task->unlock($locker);

				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]解锁了任务%task_title[%task_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=> L('ME')->id,
                    '%task_title'=> $task->title,
                    '%task_id'=> $task->id
                ]), 'journal');

				JS::refresh();
			}
		}
	}
	function index_note_unlock_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$id = $form['id'];
		$note = O('tn_note', $id);
		if ($note->id && $note->is_locked && $me->is_allowed_to('锁定', $note)) {
			if (JS::confirm(I18N::T('treenote', '您确定要执行解锁记录的操作吗?'))) {
				$note->unlock();
				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]解锁了记录%note_title[%note_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=> L('ME')->id,
                    '%note_title'=> $note->title,
                    '%note_id'=> $note->id
                ]), 'journal');
				JS::refresh();
			}
		}	
	}

	function index_project_select_change() {
		$form = Input::form();

		$project = O('tn_project', $form['project']);

		if (!$project->id) {	/* 正常操作下一般不会有此情况 */
			return;
		}

		$task_uniqid = $form['task_uniqid'];
		$task_name = $form['task_name'];
		if (isset($form['task'])) {
			$task = O('tn_task', $form['task']);
		}

		$project_select_id = $form['project_select_id'];
		
		if (isset($task) && $task->project->id == $project->id) {
			Output::$AJAX['#'.$task_uniqid] = (string) V('treenote:widgets/task_selector', ['project_select_id'=>$project_select_id, 'name' => $task_name, 'project'=>$project, 'task' => $task ]);
		}
		else {
			Output::$AJAX['#'.$task_uniqid] = (string) V('treenote:widgets/task_selector', ['project_select_id'=>$project_select_id, 'name' => $task_name, 'project'=>$project ]);
		}
	}
}
