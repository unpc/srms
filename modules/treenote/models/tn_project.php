<?php
class Tn_Project_Model extends Presentable_Model
{
	protected $object_page = [
		'view'=>'!treenote/project/index.%id[.%arguments]',
		'edit'=>'!treenote/project/edit.%id[.%arguments]',
		'add_task' => '!treenote/task/add.%id.project',
		'add_note' => '!treenote/note/add.%id.project',
		'notes' => '!treenote/embed/project_notes.%id',
		'tasks' => '!treenote/embed/project_tasks.%id',
		'delete'=>'!treenote/project/delete.%id[.%arguments]',
	];

	public function notes()
	{
		return Q("tn_note[project=$this]");
	}

	public function is_editable()
	{
		return !($this->is_complete || $this->is_locked);
	}

	public function breadcrumb()
	{
		$breadcrumb = [
			[
				'url' => $this->url(),
				'title' => H($this->title)
				]];
		return $breadcrumb;
	}

	public function progress()
	{
		$all_tasks = Q("tn_task[project=$this][parent_task=0]");
		$complete_tasks = $all_tasks->find('[is_complete=1]');
		return ['all' => $all_tasks->total_count(), 'complete' => $complete_tasks->total_count()];
	}

	public function main_tasks()
	{
		return Q($this->main_tasks_selector());
	}

	public function main_tasks_selector()
	{
		return "tn_task[project=$this][!parent_task]";
	}
	
	public function all_tasks()
	{
		return Q("tn_task[project=$this]");
	}

	public function lock($locker)
	{
		/* lock project all task add a locker */
		if (!$this->id) return FALSE;

		$this->is_locked = TRUE;
		$ret = $this->save();
		if ($ret) {
			foreach ($this->all_tasks() as $task) {
				$task->lock($locker);
			}
		}
		return $ret;
	}

	public function unlock($locker)
	{
		/* unlock project all task unlock delete a locker*/
		if (!$this->id) return FALSE;

		$this->is_locked = FALSE;
		$this->is_complete = FALSE;
		$ret = $this->save();
		if ($ret) {
			foreach ($this->all_tasks() as $task) {
				$task->unlock($locker);
			}
		}
		return $ret;
	}

	public function complete($user)
	{
		if (!$this->id) return FALSE;

		$this->is_locked = TRUE;

		$this->is_complete = TRUE;
		$ret = $this->save();
		if ($ret) {
			foreach ($this->all_tasks() as $task) {
				$task->complete($user);
			}
		}

		return $ret;
	}

	public function reactivate()
	{
		if (!$this->id) return FALSE;

		$this->is_locked = FALSE;
		$this->is_complete = FALSE;

		return $this->save();
	}

	function & links($mode = 'list') {
		$links = new ArrayIterator;
		$me = L('ME');

		switch ($mode) {
		case 'view':
			if ($this->is_complete) { /* complete */
				if ($me->is_allowed_to('激活', $this)){
					$links['activate'] = [
						'extra' => 'class="button button_activate"'.
						' q-object="project_reactivate"'.
						' q-event="click"'.
						' q-static="'.  HT(['id' => $this->id]).'"'.
						' q-src="'.  URI::url('!treenote/index').'"',
						'url' => '#',
						'text' => T('激活')
						];
				}
			}
			else{
				if ($me->is_allowed_to('完成', $this)){
					$links['complete'] = [
						'extra' => 'class="button button_tick"'.
						' q-object="project_complete"'.
						' q-event="click"'.
						' q-static="'.  HT(['id' => $this->id, 'user_id' => $me->id]).'"'.
						' q-src="'.  URI::url('!treenote/index').'"',
						'url' => "#",
						'text' => T('完成')
						];
				}
				if ($this->is_locked){ /* locked */
					if ($me->is_allowed_to('解锁', $this)){
						$links['unlock'] = [
							'extra' => 'class="button button_unlocked"'.
							' q-object="project_unlock"'.
							' q-event="click"'.
							' q-static="'.  HT(['id' => $this->id, 'locker_id' => $me->id]).'"'.
							' q-src="'.  URI::url('!treenote/index').'"',
							'url' => "#",
							'text' => T('解锁')
							];
					}
				}
				else{				/* not locked */
					if ($me->is_allowed_to('锁定', $this)){
						$links['lock'] = [
							'extra' => 'class="button button_lock"'.
							' q-object="project_lock"'.
							' q-event="click"'.
							' q-static="'.  HT(['id' => $this->id, 'locker_id' => $me->id]).'"'.
							' q-src="'.  URI::url('!treenote/index').'"',
							'url' => "#",
							'text' => T('锁定')
							];
					}
					if ($me->is_allowed_to('修改', $this)){
						$links['edit'] = [
							'extra' => 'class="button button_edit"',
							'url' => $this->url(NULL, NULL, NULL, 'edit'),
							'text' => T('修改')
							];
					}
				}
			}
			break;
		case 'list':
		default:
			if ($me->is_allowed_to('修改', $this)){
				$links['edit'] = [
					'extra' => 'class="blue"',
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text' => T('修改')
					];
			}
		}

		return (array) $links;
	}
	
}
