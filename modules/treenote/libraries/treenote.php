<?php

class Treenote {

	static function notif_callback($item) {
		$me = L('ME');
		if (!$me->id) return 0;
		$deadline = strtotime('tomorrow midnight', Date::time());
		return Q("tn_task[user=$me][is_complete=0][deadline<$deadline]")->total_count();
	}

	static function setup_profile($e) {
		Event::bind('profile.follow.tab', 'Treenote::index_follow_project_tab', 200, 'project');
		Event::bind('profile.view.tab', 'Treenote::index_profile_tab', 100);
	}

	static function setup_update($e) {
		Event::bind('update.index.tab', 'Treenote::_index_update_tab');
	}

	static function index_follow_project_tab($e, $tabs) {
		$user = $tabs->user;
		$count = $user->get_follows_count('tn_project');
		if ($count > 0) {
			Event::bind('profile.follow.content', 'Treenote::index_follow_project_content', 200, 'project');
			$tabs->add_tab('project', [
				'url'=> $user->url('follow.project'),
				'title'=>I18N::T('treenote', '项目 (%d)', ['%d'=>$count]),
			]);
		}
	}

	static function index_follow_project_content($e, $tabs) {
		$user = $tabs->user;

		$follows = $user->followings('tn_project');

		$start = (int) Input::form('st');
		$per_page = 20;
		$pagination = Lab::pagination($follows, $start, $per_page);

		$tabs->content = V('treenote:project/follows', [
			'pagination' => $pagination,
			'follows' => $follows,
		]);
	}

	static function _index_update_tab($e, $tabs) {
		$tabs->add_tab('tn_project', [
			'url'=>URI::url('!update/index.tn_project'),
			'title'=>I18N::T('treenote', '项目更新')
		]);
	}

	static function get_update_message($e, $update)
	{
		$subject = $update->subject;
		$object = $update->object;

		if ($update->action == 'edit') {
			$format = '%subject 在 %date 修改了项目 %project';
			$msg = I18N::T('treenote', $format, [
				'%subject' => URI::anchor($subject->url(), H($subject->name),
				'class="blue label"'),
				'%date' => '<strong>' . Date::fuzzy($update->ctime, TRUE) . '</strong>',
				'%project' => URI::anchor($object->url(), H($object->title),
				'class="blue label"'),
			]);
		}
		elseif ($update->action == 'task') {
			$new_data = json_decode($update->new_data, TRUE);
			if (!isset($new_data['task'])) {
				return FALSE;
			}
			else {
				$task = O('tn_task', $new_data['task']);
				if (!$task->id) {
					return FALSE;
				}
			}
			$format = '%subject 在 %date 修改了项目 %project 的任务 %task';
			$msg = I18N::T('treenote', $format, [
				'%subject' => URI::anchor($subject->url(), H($subject->name),
				'class="blue label"'),
				'%date' => '<strong>' . Date::fuzzy($update->ctime, TRUE) . '</strong>',
				'%project' => URI::anchor($task->project->url(), H($task->project->title),
				'class="blue label"'),
				'%task' => URI::anchor($task->url(), H($task->title),
				'class="blue label"'),
			]);
		}
		elseif ($update->action == 'note') {
			$new_data = json_decode($update->new_data, TRUE);
			if (!isset($new_data['note'])) {
				return FALSE;
			}
			else {
				$note = O('tn_note', $new_data['note']);
				if (!$note->id) {
					return FALSE;
				}
			}
			$format = '%subject 在 %date 修改了项目 %project 的任务 %task 的记录 %note';
			$msg = I18N::T('treenote', $format, [
				'%subject' => URI::anchor($subject->url(), H($subject->name),
				'class="blue label"'),
				'%date' => '<strong>' . Date::fuzzy($update->ctime, 'TRUE') . '</strong>',
				'%project' => URI::anchor($note->project->url(), H($note->project->title),
				'class="blue label"'),
				'%task' => URI::anchor($note->task->url(), H($note->task->title),
				'class="blue label"'),
				'%note' => URI::anchor($note->url(), H('#'.Number::fill($note->id, 6)), 'class="blue label"'),
			]);
		}

		$e->return_value = $msg;
		return FALSE;
	}

	static function index_profile_tab($e, $tabs) {
		$user = $tabs->user;
		$me = L('ME');

		if ($me->is_allowed_to('列表用户任务', $user))  {
			Event::bind('profile.view.content', 'Treenote::index_profile_content', 0, 'work');
			$tabs->add_tab('work', [
				'url' => $user->url('work'),
				'title' => I18N::HT('treenote', '工作'),
				'number' => $count > 0 ? $count : NULL,
				'weight' => 50,
			]);

		}

	}

	static function index_stat_content($e, $tabs) {
		$user = $tabs->user;
		$me = L('ME');

		$form = Lab::form();

		$selector = "tn_note[user=$user]";

		if (!$form['year']) {
			$form['year'] = Date::time();
		}
        $year = Date::format($form['year'], 'Y');

		if (!$form['week']) {
			$form['week'] = date('W', Date::time());
		}
		else {
			$form['week'] = min(max(floor($form['week']), 1), 53); // week 应为 1~53 的整数
		}
		$form['week'] = str_pad($form['week'], 2, 0, STR_PAD_LEFT);	// week No. 1~9 应补足 2 位
		$week = $form['week'];

		// echo "{$year}-W{$week}-1 - {$year}-W{$week}-7";

		$week_start = strtotime("{$year}-W{$week}-1");
		$week_end = strtotime("{$year}-W{$week}-7") + 86400;

		$form['week_start'] = $week_start;
		$form['week_end'] = $week_end;

		// echo Date::format($week_start, 'r') . '~' . Date::format($week_end, 'r');

		$selector .= "[mtime={$week_start}~{$week_end}]";

		$selector .= ":sort(mtime D)"; /* default sort */

		$notes = Q($selector);

		$form_token = Session::temp_token('treenote', 300);
		$_SESSION[$form_token] = ['selector'=>$selector, 'week'=>$form['week'], 'year'=>$year, 'user'=>$user->id];

		$filtered_notes = new ArrayIterator;
		$hours = 0;
		foreach ($notes as $n) {
			if (!$n->task->id) {
				// 如果没有任务id 则删除该无主note
				$n->delete();
				continue;
			}
			if ($filtered_notes[$n->task->id]) {
				$filtered_notes[$n->task->id]->actual_time += $n->actual_time;
			}
			else {
				$filtered_notes[$n->task->id] = $n;
			}
			$hours += round($n->actual_time / 3600, 1);
		}

		$tabs->content = V('treenote:note/stat', [
			'form' => $form,
			'notes' => $filtered_notes,
			'hours' => $hours,
			'form_token' => $form_token,
		]);

		Controller::$CURRENT->add_css('treenote:common');

	}

	static function index_review_content($e, $tabs) {
		$user = $tabs->user;
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
            $opt['mstart'] = $form['dtstart'];
        }

        if ($form['dtend_check']) {
            $opt['mend'] = $form['dtend'];
        }

        $opt['is_complete'] = FALSE;
        $opt['reviewer_id'] = $user->id;
        $opt['not']['user_id'] = $user->id;

        $opt['order_by'] = ' ORDER BY `task_status` DESC, `deadline` ASC, `priority` DESC';

        $tasks = new Search_Tn_Task($opt);

		/* pagination */
		$start = (int) $form['st'];
		$per_page = 15;
		$pagination = Lab::pagination($tasks, $start, $per_page);

		$tabs->content = V('treenote:task/list', [
			'form' => $form,
			'pagination' => $pagination,
			'tasks' => $tasks,
			'default_user' => $user,
		]);

		Controller::$CURRENT->add_css('treenote:common');
	}

	static function index_profile_content($e, $tabs) {

		$stabs = Widget::factory('tabs');
		$user = $stabs->user = $tabs->user;

		$params = Config::get('system.controller_params');


		Event::bind('profile.view.content[work]', 'Treenote::index_todo_content', 0, 'todo');
		Event::bind('profile.view.content[work]', 'Treenote::index_history_content', 0, 'history');

		$deadline = strtotime('tomorrow midnight', Date::time());
		$todo_count = Q("tn_task[is_complete=0][user=$user][deadline<$deadline]")->total_count();

		$stabs
			->add_tab('todo', [
				'title'=>I18N::HT('treenote', 'To-Do'),
				'url'=> $user->url('work.todo'),
				'number' => $todo_count,
			])
			->add_tab('history', [
				'title'=>I18N::HT('treenote', '历史'),
				'url'=> $user->url('work.history'),
			])
			->content_event('profile.view.content[work]');

		$count = Q("tn_task[is_complete=0][reviewer=$user][user!=$user][status!=0|deadline<$deadline]")->total_count();
		if ($count > 0) {
			Event::bind('profile.view.content[work]', 'Treenote::index_review_content', 0, 'review');
			$stabs->add_tab('review', [
				'url' => $user->url('work.review'),
				'title' => I18N::HT('treenote', '评审'),
				'number' => $count,
				'weight' => 60,
			]);
		}

		Event::bind('profile.view.content[work]', 'Treenote::index_stat_content', 0, 'stat');
		$stabs->add_tab('stat', [
			'url' => $user->url('work.stat'),
			'title' => I18N::HT('treenote', '统计'),
			'weight' => 70,
		]);


		$stabs
			->set('class', 'secondary_tabs')
			->select($params[2]);

		$tabs->content = V('treenote:task/tabs', ['tabs'=>$stabs]);
	}

	static function index_history_content($e, $tabs) {
		$user = $tabs->user;

		$form = Lab::form(function(&$old_form, &$form) {
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
            $opt['cstart'] = $form['dtstart'];
        }

        if ($form['dtend_check']) {
            $opt['cend'] = $form['dtend'];
        }

        //history状态为已完成
        $opt['is_complete'] = TRUE;

        //默认用户为自己
        $opt['user_id'] = $user->id;

        $opt['order_by'] = ' ORDER BY `deadline` DESC, `priority` DESC';

        $tasks = new Search_Tn_Task($opt);

		/* pagination */
		$start = (int) $form['st'];
		$per_page = 15;
		$pagination = Lab::pagination($tasks, $start, $per_page);

		$tabs->content = V('treenote:task/list', [
			'form' => $form,
			'pagination' => $pagination,
			'tasks' => $tasks,
			'default_user' => $user,
		]);

		Controller::$CURRENT->add_css('treenote:common');
	}

	static function index_todo_content($e, $tabs) {
		$user = $tabs->user;

		$form = Lab::form(function(&$old_form, &$form) {
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

        $opt['is_complete'] = FALSE;
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
			'default_user' => $user,
		]);

		Controller::$CURRENT->add_css('treenote:common');
	}

	static function on_project_saved($e, $project, $old_data, $new_data) {
		if ($project->id) Update::add_update(L('ME'), 'edit', $project);
	}

	static function on_task_saved($e, $task, $old_data, $new_data) {
		if ($old_data['project']->id != $new_data['project']->id) {
			$task->update_project();
		}

		if ($old_data['project']->id != $new_data['project']->id ||
			$old_data['parent_task']->id != $new_data['parent_task']->id) {
			/* 当树的结构改变（task的project/parent_task改变）时，要对自己关联的所有note做update_ancestors */
			foreach ($task->notes() as $note) {
				$note->update_connections();
			}
		}

		if ($new_data['id'] && !$old_data['id']) {
			//旧值不存在说明为新增。同步上传的文件到$note的目录
			$old_path = NFS::get_path(O('tn_task'), '', 'attachments', TRUE);
			$new_path = NFS::get_path($task, '', 'attachments', TRUE);
			NFS::move_files($old_path, $new_path);
		}

		if ($task->project->id) Update::add_update(L('ME'), 'task', $task->project, NULL, ['task' => $task->id]);
        //update index
        Search_Tn_Task::update_index($task);
	}

	static function on_note_saved($e, $note, $old_data, $new_data) {
		if ($new_data['id'] && !$old_data['id']) {
			//旧值不存在说明为新增。同步上传的文件到$note的目录
			$old_path = NFS::get_path(O('tn_note'), '', 'attachments', TRUE);
			$new_path = NFS::get_path($note, '', 'attachments', TRUE);
			NFS::move_files($old_path, $new_path);
		}

		if ($new_data['task']) {
            //添加新的note时，会自动update_connections，会自动update task index
			$note->update_connections();
		}
        else {
            //修改时不会update_connections，需增加update task index
            Search_Tn_Task::update_index($note->task);
        }

		if ($note->project->id) Update::add_update(L('ME'), 'note', $note->project, NULL, ['note' => $note->id]);
	}

    static function before_task_delete($e, $task) {
        Search_Tn_Task::delete_index($task);
    }

	static function project_ACL($e, $user, $perm, $project, $options) {
		if (!$user->id) return;

		switch($perm) {
		case '列表':
		case '查看':
		case '添加':
			$e->return_value = TRUE;
			return FALSE;
		case '完成':
		case '激活':
		case '解锁':
		case '锁定':
			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->id == $project->user->id) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		case '添加任务':
		case '修改':
			if (!$project->is_editable()) {
				$e->return_value = FALSE;
				return FALSE;
			}

			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->id == $project->user->id) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		case '删除':
			/*
			  project下没有task
			  Project的负责人 | 实验室的老板
			 */
			if (Q("tn_task[project=$project]")->total_count() > 0 || !$project->is_editable()) {
				$e->return_value = FALSE;
				return FALSE;
			}

			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ( $user->id == $project->user->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		default:				/* 到这儿表示perm有误 */
			$e->return_value = FALSE;
			return FALSE;
		}

	}

	static function task_ACL($e, $user, $perm, $task, $options) {
		if (!$user->id) return FALSE;
		switch($perm) {
		case '列表':
		case '查看':
		case '添加':
			$e->return_value = TRUE;
			return FALSE;
			/*
			//我实在是不清楚这段代码在这个地方有什么地方，是用来作甚的??
			if (is_string($task)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			*/
			return FALSE;
		case '评审':
		case '激活':
		case '完成':
			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ((!$task->reviewer->id && $user->id == $task->user->id)
				|| $task->can_be_reviewed_by($user)
			) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if (($user->group &&
				$user->access('管理下属机构成员的任务') && $user->group->is_itself_or_ancestor_of($task->user->group))
			) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		case '管理任务':
			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($task->project->id && $user->id == $task->project->user->id) {
				$e->return_value = TRUE;
                return FALSE;
			}
			if (($user->group &&
				$user->access('管理下属机构成员的任务') && $user->group->is_itself_or_ancestor_of($task->user->group))
			) {
                $e->return_value = TRUE;
                return FALSE;
			}

			break;
		case '锁定':
			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->id == $task->user->id
				|| ($task->project->id && $user->id == $task->project->user->id)
				|| $task->can_be_reviewed_by($user)
			) {
				$e->return_value = TRUE;
                return  FALSE;
			}

			if (($user->group &&
				$user->access('管理下属机构成员的任务') && $user->group->is_itself_or_ancestor_of($task->user->group))
			) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		case '解锁':
			$locker = Q("tn_locker[task=$task][user=$user]:limit(1)")->current();
			if ($locker->id) {
				$e->return_value = TRUE;
                return FALSE;
			}

			break;
		case '清除锁定':
			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if (($user->group &&
				$user->access('管理下属机构成员的任务') && $user->group->is_itself_or_ancestor_of($task->user->group))
			) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($task->project->id && $user->id == $task->project->user->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '删除':
			/*
			  task下没有子任务、没有记录
			  task的负责人 | project的负责人 | 实验室的老板
			 */
			if (Q("tn_task[parent_task=$task]")->total_count() > 0
				|| Q("tn_note[task={$task}]")->total_count > 0
				|| !$task->is_editable()
			) {
				$e->return_value = FALSE;
				return FALSE;
			}

			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->id == $task->user->id
				|| ($task->project->id && $user->id == $task->project->user->id)
				|| $task->can_be_reviewed_by($user)
			) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if (($user->group &&
				$user->access('管理下属机构成员的任务') && $user->group->is_itself_or_ancestor_of($task->user->group))
			) {
					$e->return_value = TRUE;
					return FALSE;
			}

			break;
		case '添加任务':
		case '添加记录':
		case '修改':
			if ( !$task->is_editable()) {
				$e->return_value = FALSE;
				return FALSE;
			}

			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->id == $task->user->id
				|| ($task->project->id && $user->id == $task->project->user->id)
				|| $task->can_be_reviewed_by($user)
			) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if (($user->group &&
				$user->access('管理下属机构成员的任务') && $user->group->is_itself_or_ancestor_of($task->user->group))
			) {
					$e->return_value = TRUE;
					return FALSE;
			}

			break;
		default:				/* 到这儿表示perm有误 */
			$e->return_value = FALSE;
			return FALSE;
		}

	}

	static function note_ACL($e, $user, $perm, $note, $options) {
		if (!$user->id) return FALSE;

		switch($perm) {
		case '列表':
		case '查看':
		case '添加':
		case '锁定':
			if (($user->group &&
				$user->access('管理下属机构成员的任务') && $user->group->is_itself_or_ancestor_of($note->user->group))
				|| $user->id == $note->user->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (is_string($note)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->id == $note->user->id) {
				$e->return_value = TRUE;
				return FALSE;
			}

			$task = $note->task;
			if ($task->id && (
				$user->id == $task->user->id
				|| ($task->project->id && $user->id == $task->project->user->id)
				|| $task->can_be_reviewed_by($user)
			)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		case '修改':
		case '删除':
			if ( !$note->is_editable()) {
				$e->return_value = FALSE;
				return FALSE;
			}

			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ( $user->id == $note->user->id ) {
				$e->return_value = TRUE;
			}

			$task = $note->task;
			if ($task->id && (
				$user->id == $task->user->id
				|| ($task->project->id && $user->id == $task->project->user->id)
				|| $task->can_be_reviewed_by($user)
			)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		default:				/* 到这儿表示perm有误 */
			$e->return_value = FALSE;
			return FALSE;
		}


	}

	static function user_ACL($e, $me, $perm, $user, $options) {
		switch($perm) {
		case '列表用户任务':
			if ($user->id == $me->id && $me->id) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($me->access('管理所有任务')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($me->access('管理下属机构成员的任务')
				&& $me->group->id && $user->group->id
				&& $me->group->is_itself_or_ancestor_of($user->group)
			) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}
	}

	static function note_attachments_ACL($e, $user, $perm, $note, $options) {
		if (!$note->id) {
			$e->return_value = TRUE;
			return FALSE;
		}

		switch($perm) {
		case '列表文件':
		case '下载文件':
			$e->return_value = TRUE;
			return FALSE;
		case '上传文件':
		case '修改文件':
		case '删除文件':
			if (!$note->is_editable()) {
				$e->return_value = FALSE;
				return FALSE;
			}

			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->id == $note->user->id) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		}
	}

	static function task_attachments_ACL($e, $user, $perm, $task, $options) {
		if (!$task->id) {
			$e->return_value = TRUE;
			return FALSE;
		}

		switch($perm) {
		case '列表文件':
		case '下载文件':
			$e->return_value = TRUE;
			return FALSE;
		case '上传文件':
		case '修改文件':
		case '删除文件':
			if (!$task->is_editable()) {
				$e->return_value = FALSE;
				return FALSE;
			}

			if ($user->access('管理所有项目')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->id == $task->user->id || $task->can_be_reviewed_by($user)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		}
	}

	static function on_achievement_edit($e, $object, $form) {
		$user = L('ME');

		$project = O('lab_project', $form[lab_project]);

		!$project->id and $project = Q("{$object} tn_project:limit(1)")->current(); /* 查询当前项目 */

		/*
		  TODO 获取项目相关仪器
		  $view = Event::trigger('achievement.project.select', $object, $lab, $project, $form);
		 */

		$data = $e->return_value;

		$e->return_value = $data . V('treenote:hooks/achievements_tn_project', [
			'project'=>$project,
			// 'view'=>$view,
			'object'=>$object
		]);
	}

	static function on_project_relation_saved($e, $form, $object) {
		$project = O('tn_project', $form['tn_project']);
		$old_projects = Q("{$object} tn_project");
		foreach ($old_projects as $old_project) {
			$object->disconnect($old_project);
		}
		$object->connect($project);
	}

	static function before_project_relation_delete($e, $object) {
		$projects = Q("{$object} tn_project");
		foreach ($projects as $project) {
			$object->disconnect($project);
		}
	}

	static function note_comment_ACL($e, $user, $perm_name, $note, $options) {
		if ($user->access('管理所有项目')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		if ($user->id == $note->user->id) {
			$e->return_value = TRUE;
			return FALSE;
		}

		$task = $note->task;
		if ($task->id && (
			($task->project->id && $task->project->user->id == $user->id)
			|| $task->can_be_reviewed_by($user)
		)) {
			$e->return_value = TRUE;
			return FALSE;
		}

	}

	static function task_comment_ACL($e, $user, $perm_name, $task, $options) {
		if ($user->access('管理所有项目')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		if ($user->id == $task->user->id) {
			$e->return_value = TRUE;
			return FALSE;
		}

		if ($task->id && (
			($task->project->id && $task->project->user->id == $user->id)
			|| $task->can_be_reviewed_by($user)
		)) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

    static function on_comment_saved($e, $comment) {
        if ($comment->object_name != 'tn_note' && $comment->object_name != 'tn_task') return;
        switch($comment->object_name) {
            case 'tn_task' :
                Search_Tn_Task::update_index($comment->object);
                break;
            case 'tn_note' :
                Search_Tn_Task::update_index($comment->object->task);
                break;
        }
    }

    static function comment_deleted($e, $comment) {
        if ($comment->object_name != 'tn_note' && $comment->object_name != 'tn_task') return;
        switch($comment->object_name) {
            case 'tn_task' :
                Search_Tn_Task::update_index($comment->object);
                break;
            case 'tn_note' :
                Search_Tn_Task::update_index($comment->object->task);
                break;
        }
    }

    static function nfs_sphinx_update($e, $object, $path, $full_path, $path_type, $stat_type) {

    	$pname = $object->name();
    	if ($pname == 'tn_note') {
    		Search_Tn_Task::update_index($object->task);	
    	}
    	elseif ($pname == 'tn_task') {
    		Search_Tn_Task::update_index($object);
    	}
    	else {
    		return;
    	}
    	
    }

	static function treenote_newsletter_content($e, $user) {
		
		$dtstart = strtotime(date('Y-m-d')) - 86400;
		$dtend = strtotime(date('Y-m-d'));
		$templates = Config::get('newsletter.template');

		$db = Database::factory();

		$template = $templates['research']['total'];
		$sql = "SELECT project_id,COUNT(*) as count FROM `tn_note` WHERE ctime>%d AND ctime<%d AND project_id != 0 GROUP BY project_id";
		$query = $db->query($sql, $dtstart, $dtend);
		if ($query) {
			$notes = $query->rows();
			$count = count($notes);
			if ($count > 0) {
				$str .= V('treenote:newsletter/total', [
				'count' => $count,
				'template' => $template,
				]);
			}
		}

		$template = $templates['research']['update'];
		$sql = "SELECT project_id,COUNT(*) as count FROM `tn_note` WHERE ctime>%d AND ctime<%d AND project_id != 0 GROUP BY project_id";
		$query = $db->query($sql, $dtstart, $dtend);
		if ($query) {
			$notes = $query->rows();
			foreach ($notes as $note) {
				$str .= V('treenote:newsletter/update', [
				'count' => $note->count,
				'note' => $note,
				'template' => $template,
				]);
			}
		}

		if (strlen($str) > 0) {
			$e->return_value .= $str;
		}
	}
}
