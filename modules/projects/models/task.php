<?php

class Task_Model extends Presentable_Model{

	protected $object_page = [
		'view'=>'!projects/task/index.%id[.%arguments]',
		'add'=>'!projects/task/add.%id.[%arguments]',
		'alert'=>'!projects/alert/index.%id[.%arguments]',
		'approve'=>'!projects/task/approve.%id',
		'delete'=>'!projects/task/delete.%id',
		'edit'=>'!projects/task/edit.%id[.%arguments]',
		'info'=>'!projects/task/index.%id.info',
	];
		
	// type
	const TYPE_TASK = 0; // task 类型 为 task
	const TYPE_CONTAINER = 1; // task 类型 为 task container
	
	// approved
	const STATUS_APPROVED = 1; // 已通过批准
	const STATUS_UNAPPROVED = 0; // 未通过批准

	static $approved = [
		self::STATUS_APPROVED => '已批准',
		self::STATUS_UNAPPROVED => '未批准',
	];
		
	//角色 attendee worker supervisor
	static $roles = [
		'attendee',
		'worker',
		'supervisor',
	];
	
	function project(){
		if($this->parent->id) return $this->parent->project();
		$project = Q("project[task={$this}]")->current();
		if($project->id) return $project;
	}
	
	/********************************************
	 * 递归检测task的dtstart和dtend是否有效
	 */
	function adjust($dtstart, $dtend, $child=FALSE){
		$tasks = [];
		// 起始时间小于结束时间
		if ($dtstart >= $dtend){
			//echo 'a';
			return FALSE;
		}
		// 是否会影响parent
		// 如果新的起止时间超出容器的时间范围，出错
		if (!$child) {
			if ($dtstart < $this->parent->dtstart || $dtend > $this->parent->dtend) {
				//echo 'b';
				return FALSE;
			}
		}
		// 是否会影响prev
		// 新的起始时间小于其前驱节点的结束时间，而前驱节点锁定， 出错
		if ($this->prev->id) {
			if ($dtstart < $this->prev->dtend) {
				if ($this->prev->locked) {
					//echo 'c';
					return FALSE;
				}
				$e = min($this->prev->dtend, $dtstart);
				$s = $e - ($this->prev->dtend - $this->prev->dtstart);
				$t = $this->prev->adjust($s, $e);
				if (!$t) {
					//echo 'd';
					return FALSE;
				}
				$tasks += $t;
			}
		}
		//是否会影响next
		// 新的结束时间大于其后续节点的起始时间，而后续节点锁定， 出错
		if ($this->next->id) {
			if ($dtend > $this->next->dtstart) {
				if ($this->next->locked) {
					//echo 'e';
					return FALSE;
				}
				$s = max($this->next->dtstart, $dtend);
				$e = $s + ($this->next->dtend - $this->next->dtstart);
				$t = $this->next->adjust($s, $e);
				if (!$t) {
					//echo 'f';
					return FALSE;
				}
				$tasks += $t;
			}
		}
		//是否影响children, 新添加的task不会有children
		if ($this->id) {
			$children = Q("task[parent={$this}]");
			if (count($children) > 0) {
				$task_tail = $children->find('[!next]:sort(dtend D):limit(1)')->current();
				$delta = $this->dtstart - $dtstart;
				//如果最后一个孩子节点顺移超出容器的时间范围，出错
				if ($task_tail->dtend  > $dtend + $delta) {
					//echo 'g';
					return FALSE;
				}
				//除锁定的孩子节点外，所有节点都顺移。
				foreach ($children as $child) {
					//锁定的节点，如果范围超出了容器的新的起止时间，出错
					if ($child->locked) {
						if ($dtstart>$child->dtstart || $dtend<$child->dtend) {
							//echo 'h';
							return FALSE;
						}
					}
					else {
						$s = $child->dtstart - $delta;
						$e = $child->dtend - $delta;
						$t = $child->adjust($s, $e, TRUE);
						if (!$t) {
							//echo 'i';
							return FALSE;
						}
						$tasks += $t;
					}
				}
			}
		}
		
		$this->dtstart = $dtstart;
		$this->dtend = $dtend;
		$tasks[$this->id] = $this;
		return $tasks;
	}


	private function _list_subtasks(&$list, $type, $indent_str, &$except_tasks, $prefix) {
		$selector_suffix = ($type == '*') ? '': "[type=$type]";
		foreach (Q("task[parent=$this]$selector_suffix") as $task) {
			if ($except_tasks[$task->id]) continue;
			$list[$task->id] = $prefix . $task->name;
			$task->_list_subtasks($list, $type, $indent_str, $except_tasks, $prefix . $indent_str);
		}
	}	

	// $except_tasks: array($key=>$v);
	function list_subtasks($type = '*', $indent_str='--', $include_self=TRUE, array $except_tasks = NULL) {
		$list = [];
		if ($include_self) {
			$list[$this->id] = $this->name;
			$prefix = '';
		}
		else {
			$prefix = $indent_str;
		}
		
		$this->_list_subtasks($list, $type, $ident_str, $except_tasks, $prefix);
		
		return $list;
	}
	
	function children($type='*'){
		$selector = "task[parent={$this}]";
		if ($type!='*') $selector .= "[type={$type}]";
		$ret = [];
		$tasks = Q($selector);
		if ($tasks->total_count()>0){
			$ret += $tasks->to_assoc('id', 'id');
			foreach($tasks as $task) {
				$ret += $task->children($type);
			}
		}
		return $ret;
	}
	
	function user_roles($user, $stop_when = NULL) {
		$connections = $this->enum_connections($user);
		$roles = array_keys($connections);
		if ($stop_when) {
			foreach($stop_when as $role) {
				if (isset($connections[$role])) {
					return $roles;
				}
			}
		}
		if ($this->parent->id) {
			$parent_roles = $this->parent->user_roles($user, ['supervisor', 'worker']);
			if (in_array('supervisor', $parent_roles) || in_array('worker', $parent_roles)) {
				$roles[] = 'parent_supervisor';
			}
		}
		return $roles;
	}
		
	//检测当前时间是否在结束时间之前，判断任务是否为完成
	function is_complete($task=''){
		$dtnow	= time();
		$dt = $task->dtend;
		if($dtnow < $dt)
			return "未完成";
		else
            return "完成";
		return "未完成";
	}
	
	function & links() {
		$links = new ArrayIterator;		
		
		$me = L('ME');
		$task = $this->task;
		$supervisors = Q("{$task} user.supervisor")->to_assoc('id', 'id');
		//if (in_array($me->id, $supervisors) || $me->access('添加/修改项目')) {
			$links['edit'] = [
				'url' => $this->url(NULL,NULL,NULL,'edit'),
				'text'  => I18N::T('projects', '编辑'),
				'extra'=>'class="blue"',
			];
	
			$links['delete'] = [
				'url'=> $this->url(NULL,NULL,NULL,'delete'),
				'tip'=>I18N::T('projects','删除'),
				'extra'=>'class="blue" confirm="'.I18N::T('projects','你确定要删除吗? 删除后不可恢复!').'"',
			];
		//}
	
		return (array) $links;
	}

	//替换进度显示条
	function progress_bar($task=''){
		
	}
	
	//查找任务的分支
	static $tmp = [];

	function branches() {
		$tasks = Q("task[parent=$this]");
		$branches = [];
		foreach ($tasks as $task) {
			if (in_array($task->id, self::$tmp)) continue;
			$branches[] = $this->brance($task);
		}
		return $branches;
	}
	
	//这个任务集的一个分支
	function brance($task) {
		$branch = [];
		$task = $this->prev_task($task);
		if (!$task->prev->id) {
			$this->next_tasks($task, $branch);
		}
		return $branch;
	}
	
	//查找这个分支上开始的任务
	function prev_task($task) {
		if ($task->prev->id) {
			$this->prev_task($task->prev);
		}
		return $task;
	}
	
	//根据一个起始任务查找一个分支
	function next_tasks($task, &$branch) {
		$branch[] = $task;
		self::$tmp[] = $task->id;
		if ($task->next->id) {
			$this->next_tasks($task->next, $branch);
		}
	}
}
