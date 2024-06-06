<?php

class Tn_Task_Model extends Presentable_Model {

	const PRIORITY_URGENT = 1;
	const PRIORITY_IMPORTANT = 2;
	const PRIORITY_NORMAL = 3;
	const PRIORITY_DEFERED = 4;

	static $priority_labels = [
		1 => '加急',
		2 => '重要',
		3 => '一般',
		4 => '延后',
	];

	static $priority_short = [
		1 => 'U',
		2 => 'I',
		3 => 'N',
		4 => 'D',
	];

	const STATUS_NONE = 0;
	const STATUS_DONE = 1;
	const STATUS_POSTPONED = 2;
	const STATUS_POSTPONED_3RD = 3;
	const STATUS_CANCEL = 4;
	const STATUS_CANCEL_3RD = 5;

	static $status_short_options = [
		1 => 'Y',
		2 => 'P',
		3 => 'P3',
		4 => 'C',
		5 => 'C3'
	];

	static $status_options = [
		0 => '未完成',
		1 => '已完成',
		2 => '延期',
		3 => '因第三方原因延期',
		4 => '取消',
		5 => '因第三方原因取消'
	];



	protected $object_page = [
		'view'=>'!treenote/task/index.%id[.%arguments]',
		'edit'=>'!treenote/task/edit.%id[.%arguments]',
		'add_child' => '!treenote/task/add.%id.task',
		'add_note' => '!treenote/note/add.%id.task',
		'notes' => '!treenote/embed/task_notes.%id',
		'tasks' => '!treenote/embed/task_tasks.%id',
		'delete'=>'!treenote/task/delete.%id[.%arguments]',
	];

	public function breadcrumb() {
		if ($this->parent_task->id && ($this->parent_task->id != $this->id)) {
			$breadcrumb = $this->parent_task->breadcrumb();
		}
		elseif ($this->project->id) {
			$breadcrumb = $this->project->breadcrumb();
		}
		$breadcrumb[] = [
			'url' => $this->url(),
			'title' => H($this->title)
		];
		return $breadcrumb;
	}

	public function progress() {
		$all_tasks = Q("tn_task[parent_task=$this]");
		$complete_tasks = $all_tasks->find('[is_complete=1]');
		return ['all' => $all_tasks->total_count(), 'complete' => $complete_tasks->total_count()];
	}

	public function is_editable() {
		/* 判断自己是否可编辑 */
		$is_editable = !($this->is_complete || $this->is_locked);

		/* 如果自己可编辑... */
		if ($is_editable) {
			if ($this->parent_task->id && ($this->parent_task->id != $this->id)) {
				/*
				  若有父任务，判断父任务是否可编辑
				  此时需避免 父任务 == 自己 的情况，以免进入死循环
				 */
				$is_editable = $is_editable && $this->parent_task->is_editable();
			}
			elseif ($this->project->id) {
				/* 否则判断项目是否可编辑 */
				$is_editable = $is_editable && $this->project->is_editable();
			}
		}

		return  $is_editable;
	}


	function all_note_ids() {		/* 单元测试里用到 */
		return $this->notes()->to_assoc('id', 'id');
	}

	public function ancestors() {
		$ancestors = [];
		if ($this->parent_task->id && ($this->parent->id != $this->id)) {
			$ancestors = $this->parent_task->ancestors();
			$ancestors[] = $this->parent_task;
		}
		return $ancestors;
	}

	public function ancestor_ids() {
		$ancestors = $this->ancestors();
		$ancestor_ids = [];
		foreach ($ancestors as $ancestor) {
			$ancestor_ids[] = $ancestor->id;
		}
		return $ancestor_ids;
	}

	function has_descendant($task) {
		$ret = FALSE;
		if ($task->parent_task->id == $this->id) {
			$ret = TRUE;
		}
		else {
			foreach ($this->child_tasks() as $child_task) {
				if ($child_task->has_descendant($task)) {
					$ret = TRUE;
					break;
				}
			}
		}

		return $ret;
	}

	public function child_tasks() {
		return Q("tn_task[parent_task=$this]");
	}

	public function update_project() {
		foreach ($this->child_tasks() as $child_task) {
			$child_task->project = $this->project;
			$child_task->save();
			$child_task->update_project();
		}
	}

	public function save($overwrite=FALSE) {
		if ($this->parent_task->id && $this->parent_task->project->id != $this->project->id) {
			$this->project = $this->parent_task->project;
		}

		if ($this->reviewer->id == $this->user->id) {
			$this->reviewer = NULL;
		}

		return parent::save($overwrite);
	}

	public function status() {

		if ($this->is_complete) {
			return '已完成';
		}
		else if ($this->is_locked) {
			return '已锁定';
		}
		else {
			return '进行中';
		}
	}

	public function notes() {
		return Q("$this tn_note");
	}

	public function lock($locker = null, $lock_child_tasks = TRUE) {
		$this->is_locked = TRUE;
		$this->save();

		if ($this->id && $locker->id) {
			$tn_locker = O('tn_locker');
			$tn_locker->user = $locker;
			$tn_locker->ctime = Date::time();
			$tn_locker->task = $this;
			$tn_locker->save();
		}

		if ($lock_child_tasks) {
			foreach ($this->child_tasks() as $task) {
				$task->lock($locker, TRUE);
			}
		}


		foreach ($this->notes() as $note) {
			$note->lock();
		}
	}

	public function unlock($locker = null) {

	 	if ($locker->id) {
	 		$tn_locker = Q("tn_locker[task={$this}][user={$locker}]:limit(1)")->current();
		 	if ($tn_locker->id) {
		 		$tn_locker->delete();
		 	}

		 	if (Q("tn_locker[task={$this}]")->total_count() == 0) {
		 		$this->is_locked = FALSE;
		 		$this->save();
		 	}

		 	foreach ($this->child_tasks() as $task) {
		 		$task->unlock($locker);
		 	}
		 	
	 	}
	}

	public function clean_lock() {

		Q("tn_locker[task={$this}]")->delete_all();
		$this->is_locked = FALSE;
		$this->save();
		foreach ($this->child_tasks() as $task) {
			$task->clean_lock();
		}

	}
	public function complete($user, $complete_child_tasks = TRUE) {

		$this->lock($user);
		$this->is_complete = TRUE;
		if ($this->status == self::STATUS_NONE) {
			$now = Date::time();
			$deadline = strtotime('tomorrow midnight', $this->deadline);
			if ($now > $deadline) {
				$this->status = self::STATUS_POSTPONED;
			}
			else {
				$this->status = self::STATUS_DONE;
			}
		}

		$this->save();

		if ($complete_child_tasks) {
			foreach ($this->child_tasks() as $task) {
				$task->complete($user, TRUE);
			}
		}
	}

	public function reactivate($reactivate_project = TRUE) {
		/* reactivate will unlock parent_task and project  */

		$this->is_complete = FALSE;
		if ($this->status == self::STATUS_DONE 
			|| $this->status == self::STATUS_POSTPONED
			|| $this->status == self::STATUS_POSTPONED_3RD
		) {
			$this->status = self::STATUS_NONE;
		}
		$this->save();

		if ($this->parent_task->id && ($this->parent->id != $this->id)) {
			$this->parent_task->reactivate(TRUE);
		}

		if ($reactivate_project && $this->project->id) {
			$this->project->reactivate();
		}
	}

	function & links($mode = 'index') {

		$links = new ArrayIterator;
		$me = L('ME');

		switch ($mode) {
		case 'view':
			if ($this->is_locked && $me->is_allowed_to('清除锁定', $this)) {
				$links['lock'] = [
				'extra' => 'class="button button_unlocked"'.
				' q-object="clean_lock"'.
				' q-event="click"'.
				' q-static="'.  H(['id' => $this->id, 'locker_id' => $me->id]).'"'.
				' q-src="'.  URI::url('!treenote/index').'"',
					'url' => "#",
					'text' => T('清除锁定')
				];	
			}
		case 'index':
			if ($this->is_complete) { /* complete */
				if ($me->is_allowed_to('评审', $this)) {
					$links['activate'] = [
						'extra' => 'class="button button_activate"'.
						' q-object="task_reactivate"'.
						' q-event="click"'.
						' q-static="'.  H(['id' => $this->id]).'"'.
						' q-src="'.  URI::url('!treenote/index').'"',
							'url' => '#',
							'text' => T('激活')
						];
				}
			}
			else{				/* not complete */
				if ($me->is_allowed_to('评审', $this)) {
					$links['complete'] = [
						'extra' => 'class="button button_tick"'.
						' q-object="complete"'.
						' q-event="click"'.
						' q-static="'.  H(['id' => $this->id, 'user_id' => $me->id]).'"'.
						' q-src="'.  URI::url('!treenote/task').'"',
							'url' => "#",
							'text' => T('完成')
						];
				}
				if (!$this->is_locked) {
					/* not locked */
					if ($me->is_allowed_to('锁定', $this)) {
						$links['lock'] = [
							'extra' => 'class="button button_lock"'.
							' q-object="task_lock"'.
							' q-event="click"'.
							' q-static="'.  H(['id' => $this->id, 'locker_id' => $me->id]).'"'.
							' q-src="'.  URI::url('!treenote/index').'"',
								'url' => "#",
								'text' => T('锁定')
							];
					}
					if ($me->is_allowed_to('修改', $this)) {
						$links['edit'] = [
							'extra' => 'class="button button_edit"'.
							' q-object="edit_task"'.
							' q-event="click"'.
							' q-static="'. H(['id' => $this->id, 'mode' => $mode]).'"'.
							' q-src="' . URI::url('!treenote/task'). '"'
							,
							'url' => '#',
							'text' => T('修改')
						];
					}
				}
			}
			break;
		default:
			if (!$this->is_complete && !$this->is_locked) {
				if ($me->is_allowed_to('添加', 'tn_note')) {
					$links['add_note'] = [
						'extra' => 'class="blue"',
						'url' => $this->url(NULL, NULL, NULL, 'add_note'),
						'tip' => I18N::HT('treenote', '添加记录')
					];
				}
			}
			if (!$this->is_complete) { /* complete */
				if ($me->is_allowed_to('评审', $this)) {
					$links['complete'] = [
						'extra' => 'class="blue"'.
						' q-object="task_complete"'.
						' q-event="click"'.
						' q-static="'.  HT(['id' => $this->id]).'"'.
						' q-src="'.  URI::url('!treenote/index').'"',
							'url' => "#",
							'text' => I18N::HT('treenote', '完成')
						];
				}
			}
		}

		return (array) $links;
	}

	function can_be_reviewed_by($user) {
		if (!$this->id || !$user->id) return FALSE;
		if ($this->reviewer->id == $user->id) return TRUE;
		if (!$this->parent_task->id) return FALSE;
		if ($this->parent_task->user->id == $user->id) return TRUE;
		// 不级联 只是上一级任务的负责人能够review本级任务
		//return $this->parent_task->can_be_reviewed_by($user);
		return FALSE;
	}

    //获取记录附件中文件
    function get_notes_files() {
        $files = [];
        foreach($this->notes() as $note) {
            $note_path = NFS::get_attachments_path($note);
            $note_files = NFS::file_list($note_path, NULL);
            foreach((array) $note_files as $file) {
                $files[] = $file['name'];
            }
        }
        return $files;
    }

    //获取任务附件中的文件
    function get_files() {
        $files = [];
        $task_path = NFS::get_attachments_path($this);
        $task_files = NFS::file_list($task_path, NULL);
        foreach((array) $task_files as $file) {
            $files[] = $file['name'];
        }
        return $files;
    }

    function __get($key) {
        if ($key == 'name') {
            return $this->title;
        }
        else {
            return parent::__get($key);
        }
    }
}
