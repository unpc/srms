<?php

class Task_Controller extends Base_Controller {

	function index($id = 0, $tab = '') {
		$task = O('tn_task', $id);
		if (!$task->id) {
			URI::redirect('error/404');
		}

		if (!L('ME')->is_allowed_to('查看', $task)) {
			URI::redirect('error/401');
		}

		$content = V('task/index', ['task' => $task]);

		Event::bind('task.index.content', [$this, '_index_tasks'], 0, 'tasks');
		Event::bind('task.index.content', [$this, '_index_notes'], 0, 'notes');

		$content->secondary_tabs = Widget::factory('tabs')
       		->tab_event('task.index.tab')
			->content_event('task.index.content')
			->set('task', $task);

		$content->secondary_tabs
			->add_tab('tasks', [
				'url' => $task->url('tasks'),
				'title' => I18N::T('treenote', '任务列表')
			])
			->add_tab('notes', [
				'url' => $task->url('notes'),
				'title' => I18N::T('treenote', '记录列表')
			]);

		if (!$tab) {
			if (Q("tn_task[parent_task=$task]")->total_count() > 0) {
				$tab = 'work';
			}
			else {
				$tab = 'notes';
			}
		}

		$content->secondary_tabs->select($tab);

		$this->layout->body->primary_tabs
			->add_tab('task_view', [
						  '*' => $task->breadcrumb()
						  ])
			->select('task_view')
			->set('content', $content);
	}

	function _index_tasks($e, $tabs) {

		$task = $tabs->task;
		$me = L('ME');

		$form = Lab::form(function(&$old_form, &$form) {
			unset($form['type']);
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
		
        $opt = [];

        if ($form['content']) {
            $opt['content'] = $form['content'];
        }

        if ($form['priority']) {
            $opt['priority'] = $form['priority'];
        }

        if (isset($form['status']) && $form['status'] !== '') {
            $opt['status'] = $form['status'];
        }

        if ($form['dtstart_check']) {
            $opt['dstart'] = $form['dtstart'];
        }

        if ($form['dtend_check']) {
            $opt['dend'] = $form['dtend'];
        }

        $opt['parent_task_id'] = $task->id;
        $opt['user_id'] = $user->id;
        $opt['order_by'] = ' ORDER BY `deadline` ASC, `priority` DESC';

        $tasks = new Search_Tn_Task($opt);
		/* pagination */
		$start = (int) $form['st'];
		$per_page = 15;
		$pagination = Lab::pagination($tasks, $start, $per_page);
		
		$tabs->content = V('treenote:task/list', [
			'form' => $form,
			'pagination' => $pagination,
			'tasks' => $tasks,
			'task' => $task,
		]);
	}

	function _index_notes($e, $tabs) {
		$task = $tabs->task;
		
		$me = L('ME');

		$form = Lab::form(function(&$old_form, &$form) {
				unset($form['type']);
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

		$selector = "$task tn_note";
		//按时间搜索
		if($form['dtstart_check']){
			$dtstart = Q::quote($form['dtstart']);
			$selector .= "[mtime>={$dtstart}]";
		}

		if($form['dtend_check']){
			$dtend = Q::quote($form['dtend']);
			$selector .= "[mtime<={$dtend}]";
		}

		if (!$form['dtstart_check'] && !$form['dtend_check']) {
			$dtend_date = getdate(time());
			$form['dtend'] = mktime(23, 59, 59, $dtend_date['mon'], $dtend_date['mday'], $dtend_date['year']);
			$form['dtstart'] = $form['dtend'] - 2592000;
 		}

		if ($form['title']) {
			$title = Q::quote($form['title']);
			$selector .= "[title*={$title}]";
		}

		$selector .= ":sort(mtime D)";

		$notes = Q($selector);

		$start = (int) $form['st'];
		$per_page = 20;

		$pagination = Lab::pagination($notes, $start, $per_page);

		$tabs->content = V('note/task_notes', [
			   'form' => $form,
			   'pagination' => $pagination,
			   'task' => $task,
			   'notes' => $notes,
		   ]);
		
	}

	function _index_attachments($e, $tabs) {
		if(!Module::is_installed('nfs')) URI::redirect('error/404');
		$task = $tabs->task;
		$tabs->content = V('task/attachments', [
							'object'=>$task,
							'path_type'=>'attachments'
						]);
	}

	function task_tasks($id=0) {

		$task = O('tn_task', $id);
		if (!$task->id) {
			return;
		}

		$tasks = Q("tn_task[parent_task=$task]:sort(deadline A, priority A)");
		
		echo V('task/sidebar_tasks', [
			   'task' => $task,
			   'tasks' => $tasks,
			 ]);		

		$tabs->content = V('note/list', ['root'=>$tabs->task]);
	}
	
}


class Task_AJAX_Controller extends AJAX_Controller {
	function index_preview_click() {
		 $form = Input::form();
		 $task = O('tn_task',$form['id']);
		
		 if (!$task->id) return;
		
		 Output::$AJAX['preview'] = (string)V('treenote:task/preview', ['task'=>$task]);
	}

	function index_new_task_click() {
		$me = L('ME');
		$form = Input::form();
		$parent_task = O('tn_task', $form['parent_task']);
		$project = O('tn_project', $form['project']);
		
		if ($parent_task->id && $me->is_allowed_to('添加任务', $parent_task)) {
			$can_add_task = TRUE;
		}
		
		if ($project->id && $me->is_allowed_to('添加任务', $project)) {
			$can_add_task = TRUE;
		}
		
		if (!$parent_task->id && !$project->id && $me->is_allowed_to('添加', 'tn_task')) {
			$can_add_task =  TRUE;
		}
		
		if ($me->id && $can_add_task) {
			JS::dialog(V('treenote:task/quick_form', ['form'=>$form]), ['title'=>I18N::HT('treenote', '添加任务'), 'drag'=>TRUE, 'keyboard'=>FALSE]);
		}
	}

	function index_edit_task_click() {
		$me = L('ME');
		$form = Form::filter(Input::form());
		$task = O('tn_task', $form['id']);
		if ($me->id && $task->id && $me->is_allowed_to('修改', $task)) {
			$form['title'] = $task->title;
			$form['description'] = $task->description;
			$form['user'] = $task->user->id;
			$form['reviewer'] = $task->reviewer->id;
			$form['deadline'] = $task->deadline;
			$form['status'] = $task->status;
			$form['expected_hours'] = round($task->expected_time/3600, 1);
			$form['priority'] = $task->priority;
			JS::dialog(V('treenote:task/quick_form', ['form'=>$form, 'task'=>$task]), ['title'=>I18N::HT('treenote', '编辑任务'), 'drag'=>TRUE, 'keyboard'=>FALSE]);
		}
	}

	function index_exist_task_form_submit() {
		$me = L('ME');
		if (!$me->id) {
			return;
		}

		$form = Input::form();
		
		$task = O('tn_task', $form['task']);
		if (!$task->id || !$me->is_allowed_to('修改', $task)) {
			JS::alert(I18N::T('treenote', '您无权选定该任务!'));
			return;
		}

		if ($form['parent_task']) {
			$parent_task = O('tn_task', $form['parent_task']);
			if (!$parent_task->id || !$me->is_allowed_to('修改', $parent_task)) {
				return;
			}

			if ($parent_task->id == $task->id) {
				JS::alert(I18N::T('treenote', '不能添加自身作为子任务!'));
				return;
			}

			if (in_array($task->id, $parent_task->ancestor_ids())) {
				JS::alert(I18N::T('treenote', '不能添加上级任务为子任务!'));
				return;
			}

			$task->parent_task = $parent_task;
			$task->save();

		}
		else {
			$project = O('tn_project', $form['project']);
			if (!$project->id || !$me->is_allowed_to('修改', $project)) {
				JS::alert(I18N::T('treenote', '您无权修改该项目!'));
				return;
			}

			$task->project = $project;
			$task->parent_task = NULL;
			$task->save();
		}

		JS::refresh();
	}

	function index_task_form_submit() {
		$me = L('ME');
		if (!$me->id) {
			return;
		}
	
		$form = Form::filter(Input::form());
		$task = O('tn_task', $form['id']);
        $parent_task = O('tn_task', $form['parent_task']);

		if (!$task->id) {
			$project = O('tn_project', $form['project']);
			
			if ($parent_task->id && $me->is_allowed_to('添加任务', $parent_task)) {
				$can_add_task = TRUE;
			}
			
			if ($project->id && $me->is_allowed_to('添加任务', $project)) {
				$can_add_task = TRUE;
			}

			if (!$parent_task->id && !$project->id && $me->is_allowed_to('添加', 'tn_task')) {
				$can_add_task =  TRUE;
			}
			
			if (!$can_add_task) return;
			
			$add_op = TRUE;
		}
		else {
			if (!$me->is_allowed_to('修改', $task)) {
				return;
			}
			$add_op = FALSE;
		}

		/* validation */
		$form->validate('title', 'not_empty', I18N::T('treenote', '请填写任务名称'));
		if (isset($form['user'])) {
			$form->validate('user', 'not_empty', I18N::T('treenote', '负责人不能为空'));
		}

		if (!$task->id) {
			$form
				->validate('priority', 'not_empty', I18N::T('treenote', '请选择优先级'))
				->validate('deadline', 'not_empty', I18N::T('treenote', '请选择预计结束时间'));
		}
        else {

            if (in_array($task->id, $parent_task->ancestor_ids())) {
                $form->set_error('parent_task', I18N::T('treenote', '上级任务不能为当前任务子任务!'));
            }
        }

		if ($form->no_error) {

			/* assignment */
			$task->title = $form['title'];
			$task->description = $form['description'];
			
			$task->status = $form['status'];

			if (!$task->id || $me->is_allowed_to('评审', $task)) {
				$task->parent_task = O('tn_task', $form['parent_task']);
				if ($task->parent_task->id) {
					$task->project = $task->parent_task->project;
				}
				$task->priority = $form['priority'];
				$task->deadline = $form['deadline'];
				$task->expected_time = floor($form['expected_hours'] * 3600);
	
				$task->user = O('user', $form['user']);
				if (!$task->user->id) $task->user = $me;
				$task->reviewer = O('user', $form['reviewer']);
				if ($task->reviewer->id == $task->user->id) $task->reviewer = NULL;
				// 1. reviewer 允许为空;
				// 2. 当 reviewer 和 user 为一个人时, reviewer 强置为空, 因为自己不用给自己评审;
			}

			if (!$task->id && !$task->project->id) {
				$task->project = O('tn_project', $form['project']);
			}

			if ($task->save()) {
				/* 记录日志 */
                if ($add_op) {
                    Log::add(strtr('[treenote] %user_name[%user_id]添加了任务%task_title[%task_id]', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%task_title'=> $task->title,
                        '%task_id'=> $task->id
                    ]), 'journal');
                }
                else {
                    Log::add(strtr('[treenote] %user_name[%user_id]修改了任务%task_title[%task_id]', [
                        '%user_name'=> $me->name,
                        '%user_id'=> $me->id,
                        '%task_title'=> $task->title,
                        '%task_id'=> $task->id
                    ]), 'journal');
                }

				if ($add_op) {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('treenote', '任务添加成功！'));
				}
				else {
					Lab::message(Lab::MESSAGE_NORMAL, I18N::T('treenote', '任务更新成功！'));
				}

				JS::refresh();
			}
			else {
				if ($add_op) {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('treenote', '任务添加失败！'));
				}
				else {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('treenote', '任务更新失败！'));
				}
				JS::dialog(V('treenote:task/quick_form', ['form'=>$form, 'task'=>$task]), ['drag'=>TRUE]);
			}

		}
		else {
			JS::dialog(V('treenote:task/quick_form', ['form'=>$form, 'task'=>$task]), ['drag'=>TRUE, 'title'=>I18N::HT('treenote', '添加任务') ]);
		}

	}

	function index_complete_click()
	{
		if (JS::confirm(I18N::T('treenote', '此操作会同时完成所有后代任务，并锁定任务下所有记录。'))) {
			$id = Input::form('id');
			$user_id =  Input::form('user_id');
			$user = O('user', $user_id);
			$task = O('tn_task', $id);
			if (L('ME')->is_allowed_to('评审', $task)) {
				$task->complete($user);
				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]完成了任务%task_title[%task_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=>  L('ME')->id,
                    '%task_title'=> $task->title,
                    '%task_id'=>  $task->id
                ]), 'journal');
			}
			JS::refresh();
		}
	}

	function index_delete_task_click() {
		if (JS::confirm(I18N::T('treenote', '您确定希望删除该任务吗?'))) {
			$form = Input::form();
			$id = $form['id'];
			$mode = $form['mode'];
			$task = O('tn_task', $id);
			if ($task->id && L('ME')->is_allowed_to('删除', $task)) {

				/* 记录日志 */
                Log::add(strtr('[treenote] %user_name[%user_id]删除了任务%task_title[%task_id]', [
                    '%user_name'=> L('ME')->name,
                    '%user_id'=>  L('ME')->id,
                    '%task_title'=> $task->title,
                    '%task_id'=> $task->id
                ]), 'journal');

				$task->delete();

				if ($mode == 'view') {
					if ($task->parent_task->id) {
						JS::redirect($task->parent_task->url());
					}
					elseif ($task->project->id) {
						JS::redirect($task->project->url());
					}
					else {
						JS::redirect('!treenote/work');
					}
				}
				else {
					JS::refresh();
				}
			}
			else {
				JS::alert(I18N::T('treenote', '无法删除该任务!'));
				JS::close_dialog();
			}
		}
	}

	function index_locate_task_submit() {
		$tid = Input::form('task');
		$task = O('tn_task', $tid);
		if ($task->id) {
			JS::redirect($task->url());
		}
	}
}
