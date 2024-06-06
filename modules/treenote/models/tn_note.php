<?php
class Tn_Note_Model extends Presentable_Model
{

	protected $object_page = [
		'view'=>'!treenote/note/index.%id[.%arguments]',
		'edit'=>'!treenote/note/edit.%id[.%arguments]',
		'delete'=>'!treenote/note/delete.%id[.%arguments]',
	];

	function save($overwrite = FALSE)
	{
		if ($this->project->id != $this->task->project->id) {
			$this->project = $this->task->project;
		}

		return parent::save($overwrite);
	}

	public function is_editable()
	{
		return !($this->is_complete || $this->is_locked) && $this->task->is_editable();
	}

	public function update_connections()
	{
		$old_tasks = $this->ancestor_tasks();
		// 使用 length 强制让 Q 实例化，否则后面会出问题
		$old_tasks->total_count();

		foreach ($this->task->ancestors() as $t) {
			$t->connect($this);
			$t->mtime = max($t->mtime, $this->mtime);
			$t->save();
			unset($old_tasks[$t->id]);
		}

		$this->task->connect($this);
		$this->task->mtime = max($this->task->mtime, $this->mtime);
		$this->task->save();
		unset($old_tasks[$this->task->id]);

		foreach ($old_tasks as $t) {
			$t->disconnect($this);
		}
	}

	function ancestor_tasks()
	{
		return Q("{$this} tn_task");
	}

	public function breadcrumb()
	{
		$breadcrumb = $this->task->breadcrumb();
		$breadcrumb[] = [
			'url' => $this->url(),
			'title' => I18N::HT('treenote', '记录[#%id]', ['%id'=>Number::fill($this->id,6)]),
			];
		return $breadcrumb;
	}

	public function lock()
	{
		$this->is_locked = TRUE;
		$this->save();
	}

	public function unlock()
	{
		$this->is_locked = FALSE;
		$this->save();
	}

	public function complete()
	{
		$this->lock();

		$this->is_complete = TRUE;
		$this->save();
	}

	public function reactivate()
	{
		$this->unlock();

		$this->is_complete = FALSE;
		$this->save();

		$this->task->reactivate();
	}

	function & links($mode = 'list') {
		$links = new ArrayIterator;
		$me = L('ME');

		switch ($mode) {
		case 'view':
			if ($this->is_locked){ /* locked */
				if ($me->is_allowed_to('锁定', $this)){
					$links['unlock'] = [
						'extra' => 'class="button button_unlocked"'.
						' q-object="note_unlock"'.
						' q-event="click"'.
						' q-static="'.  H(['id' => $this->id]).'"'.
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
						' q-object="note_lock"'.
						' q-event="click"'.
						' q-static="'.  H(['id' => $this->id]).'"'.
						' q-src="'.  URI::url('!treenote/index').'"',
						'url' => "#",
						'text' => T('锁定')
						];
				}
			}

			if ($me->is_allowed_to('修改', $this)){
				$links['edit'] = [
					'extra' => 'class="button button_edit"'.
					' q-object="edit_note"'.
					' q-event="click"'.
					' q-static="'. H(['id'=>$this->id]).'"'.
					' q-src="'. URI::url('!treenote/note') .'"'
					,
					'url' => '#',
					'text' => T('修改')
					];
			}

			break;
		// case 'list':
		default:
			if ($me->is_allowed_to('修改', $this)){
				$links['edit'] = [
					'extra' => 'class="blue prevent_default"',
					'url' => $this->url(NULL, NULL, NULL, 'edit'),
					'text' => T('修改')
					];
			}
		}

		return (array) $links;
		
	}

    function delete() {
        $task = $this->task;
        //先执行删除，再update_index
        $ret = parent::delete();
        Search_Tn_Task::update_index($task);
        return $ret;
    }
}
