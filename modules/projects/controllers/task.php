<?php

class Task_Controller extends Base_Controller{
	
	private $project;
	
	function index($tid=0, $mode='info'){
		
		$task = O('task', $tid);
		if(!$task->id){
			URI::redirect('error/404');
		}
						
		$me = L('ME');
		
		// 没有“查看所有项目”权限 并且 不在team中 -> 出错
		if (!$me->access('查看所有项目')) {
			// task 的 组成员: supervisor, worker, attendee
			// task->parent 的 组成员: supervisor, worker === task 的 parent_supervisor
			$roles = $task->user_roles($me);
			if(
				!in_array('supervisor', $roles)
				&&
				!in_array('worker', $roles)
				&&
				!in_array('attendee', $roles)
				&&
				!in_array('parent_supervisor ', $roles)
			) {
				URI::redirect('error/401');
			}
			// $task->user_roles($me); ==> ["parent_supervisor", "supervisor", "worker", "attendee"]
		}
		
		$this->project = $project = $task->project();

		$primary_tabs = $this->layout->body->primary_tabs;
		$primary_tabs->add_tab('project', [
			'url'=> $project->task->url(),
			'title'=>I18N::T('projects', '查看%project', ['%project'=>$project->task->name]),
		]);
		
		if ($task->parent->id && $task->parent->id!=$project->task->id) {
			$primary_tabs
				->add_tab('parent', [
					'url'=> $task->parent->url(),
					'title'=>I18N::T('projects', '查看%task', ['%task'=>$task->parent->name]) ,
				]);
		}
		
		if ($task->parent->id) {
			$primary_tabs
				->add_tab('current', [
					'url'=> $task->url(),
					'title'=>I18N::T('projects', '查看%task', ['%task'=> $task->name]) ,
				])
				->select('current');
		}
		else {
			$primary_tabs->select('project');
		}
		
		$ms = ['timeline', 'info', 'list'];
		
		if (!in_array($mode, $ms)) {
			$mode = 'timeline';
		}
		
		if($task->type!=Task_Model::TYPE_CONTAINER && $mode=='timeline') {
			$mode = 'info';
		}

		$method = '_index_'.$mode;
		
		$args = [
			'task'=>$task,
			'mode'=>$mode
		];
		
		if(method_exists($this, $method)) call_user_func([$this, $method], $task);

	}
	
	function _index_info($task){
		
		$tasks = Q("task[parent=$task]");
		$project = $this->project;
		$this->add_css('projects:info');
		$this->layout->body->primary_tabs->content
						= V('task/info',[
							'project'=>$project,
							'task'=>$task,
							'tasks'=>$tasks
						]);
	}

	function _index_timeline($task){
				
		$project = $this->project;
		
		//从当前这个礼拜时间算起
		$date = getdate();
		$dtstart = mktime(0,0,0,$date['mon'],$date['mday']-$date['wday'],$date['year']);
		$dtend = $dtstart + 604800;
		$dtprev = $dtstart - 604800;
		$dtnext = $dtstart + 604800;
		
		$tasks = Q("task[parent=$task][dtend>=$dtstart][dtstart<=$dtend]");
		
		$this->add_js('projects:timeline');
		$this->add_css('projects:timeline');
		$this->add_css('projects:info');
		$this->layout->body->primary_tabs->content
						= V('timeline/index',[
							'project'=>$project,
							'parent_task'=>$task,
							'tasks'=>$tasks,
							'dtstart'=>$dtstart,
							'dtprev'=>$dtprev,
							'dtnext'=>$dtnext
						]);
	}

	function _index_list($task){
		
		$tasks = Q("task[parent={$task}]");
		$this->layout->body->primary_tabs->content
						= V('task/list',[
							'tasks'=>$tasks
						]);
	}
	
	function add($tid=0, $prev_tid=0){
		$this->edit(0, $tid, $prev_tid);
	}


	function edit($tid=0, $parent_tid=0, $prev_tid=0){
		$task = O('task', $tid);
		$me = L('ME');
		if ($task->id){
			$project = $task->project();
			if (!$task->parent->id) {
				URI::redirect($project->url(NULL, NULL, NULL, 'edit'));
			}
			// 没有（添加/修改任务）权限  并且  （不是this的 supervisors 或者 不是parent的workers或者supervisors）
			if (!$me->access('添加/修改任务', $task)) {
			$roles = $task->user_roles($me);
			if(
				!in_array('supervisor', $roles)
				&&
				!in_array('parent_supervisor ', $roles)
			) {
					URI::redirect('error/401');
				}
			}
		}
		else {
			$parent_task = O('task', $parent_tid);
			if($parent_task->id){
				// 没有（添加/修改任务）权限  并且  （不是workers或者supervisors）
				if (!$me->access('添加/修改任务', $parent_task)) {
					$roles = $task->user_roles($me);
					if(
						!in_array('supervisor', $roles)
						&&
						!in_array('worker', $roles)
						&&
						!in_array('parent_supervisor ', $roles)
					) {
						URI::redirect('error/401');
					}
				}
				$project = $parent_task->project();
			}
		}
				
		if (!$project->id || !$project->task->id) {
			URI::redirect('error/404');
		}
				
		$form = Form::filter(Input::form());
		if ($form['t']) $task->type = $form['t'];
		if (isset($form['submit'])) {
			$form->validate('name', 'not_empty', I18N::T('projects', '任务名称不能为空！'));
			if ($form->no_error) {
				try{
					//判断parent的有效性
					$parent = O('task', $form['parent']);
					if(!$parent->id) {
						Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','父级任务不存在!'));
						throw new Exception;
					}
					if($parent->type!=Task_Model::TYPE_CONTAINER) {
						Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','父级任务不是任务目录！'));
						throw new Exception;
					}
					if($task->id){
						if($parent->id==$task->id){
							Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','任务的父级不能为自己'));
							throw new Exception;
						}
						$children = $task->children(Task_Model::TYPE_CONTAINER);
						if (in_array($parent->id, $children)) {
							Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','任务的父级不能为自己的子任务'));
							throw new Exception;
						}
					}
				
					//判断prev的有效性
					if (isset($form['prev'])) {
						$prev = O('task', $form['prev']);
						if($prev->next->id && $prev->next->id!=$task->id) {
							Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','指定的上级任务已经被占用!'));
							throw new Exception;
						}
						if($prev->id && $prev->parent->id!=$parent->id) {
							Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','指定的前驱任务的父级任务与指定的父级任务不一致!'));
							throw new Exception;
						}
						//为task制定了新的prev
						if ($prev->id && $prev->id!=$task->prev->id) {
							$old_prev = $task->prev;
							$task->prev = $prev;
							$prev->next = $task;
						}
						else{
							$prev = NULL;
						}
					}
					// 设置task从前的prev的next为NULL
					elseif ($task->prev->id) {
						$old_prev = $task->prev;
						$task->prev = NULL;
					}
				
					$task->parent = $parent;
			
					$res = $task->adjust($form['dtstart'], $form['dtend']);
					if (!is_array($res) || count($res)<1) {
						Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','指定的起止时间无效！'));
						throw new Exception;
					}
					//如果把task从container => task, 应该查看其有无child
					if (isset($form['task_container'])) {
						if ($task->id && $task->type==Task_Model::TYPE_CONTAINER) {
							$children = Q("task[parent={$task}]");
							if (count($children)>0) {
								Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','该任务包含子任务，不能保存为单独的任务！'));
								throw new Exception;
							}
						}
						$task->type = $form['task_container'] ? Task_Model::TYPE_CONTAINER : Task_Model::TYPE_TASK;				
					}
					
					$task->name = $form['name'];
					$task->description = $form['description'];
					$task->dtstart = $form['dtstart'];
					$task->dtend = $form['dtend'];
					$task->milestone = $form['task_milestone'] ? true : false;
					$task->locked = $form['locked'] ? true : false;
			
					if($task->save()) {
						if($old_prev->next->id){
							$old_prev->next = NULL;
							$old_prev->save();
						}
						if($prev->id){
							$prev->save();
						}
						foreach($res as $t){
							$t->save();
						}
						//attendees
						$attendees = json_decode($form['attendees'], true);				
						if(!is_array($attendees))  $attendees = [];
						Task::replace_to_connection($task, array_keys($attendees), 'attendee');
						//workers
						$workers = json_decode($form['workers'], true);				
						if(!is_array($workers))  $workers = [];
						Task::replace_to_connection($task, array_keys($workers), 'worker');
						//supervisors
						$roles = $task->user_roles($me);
						if ($me->access('添加/修改任务', $task) || in_array('parent_supervisor ', $roles)) {
							$supervisors = json_decode($form['supervisors'], true);				
							if(!is_array($supervisors))  $supervisors = [];
							Task::replace_to_connection($task, array_keys($supervisors), 'supervisor');
						}
						
						Lab::message(LAB::MESSAGE_NORMAL,I18N::T('projects','任务更新成功!'));
						URI::redirect($task->url(NULL, NULL, NULL, 'edit'));
					}
					else{
						Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','任务更新失败!'));
					}
				}
				catch(Exception $e){
				}
			}
		}		
		
		$primary_tabs = $this->layout->body->primary_tabs;
		$primary_tabs->add_tab('project', [
			'url' => $project->task->url(),
			'title' => I18N::T('projects', '查看%project', ['%project'=>$project->task->name]),
		]);
		
		//如果存在 task_id 则显示编辑任务tab
		if ($task->id) {
			$primary_tabs->add_tab('edit',[
						'url'=> $task->url(NULL, NULL, NULL, 'edit'),
						'title'=>I18N::T('projects','编辑任务'),
					])
			->select('edit');
		}
		//如果不存在 task_id 则显示添加任务tab
		else {
			$primary_tabs->add_tab('add',[
						'url'=> $parent_task->url($prev_tid, $form['t'] ? 't='.$form['t'] : NULL, NULL, 'add'),
						'title'=>I18N::T('projects','添加任务'),
					])
			->select('add');
		}
		
		$primary_tabs->content = V('task/edit',[
										'task' => $task,
										'form' => $form,
										'parent_task' => $parent_task,
										'prev_task_id' => $form->no_error ? $prev_tid : (isset($form['prev']) ? $form['prev'] : 0),
										'project' => $project,
									]);		
	}
	
	function delete($tid=0){

		$task = O('task',$tid);

		if (!$task->id) {
			URI::redirect('error/404');
		}
		
		if (!$task->parent->id) {
			URI::redirect('error/401');
		}
		$me = L('ME');
		// 没有（添加/修改任务）权限  并且  （不是parent的workers或者supervisors）
		if (!$me->access('添加/修改任务', $task)) {
			$roles = $task->user_roles($me);
			if(!in_array('parent_supervisor ', $roles)) {
				URI::redirect('error/401');
			}
		}
		
		//该任务包含子任务不能够删除
		if(Q("task[parent={$task->id}]")->total_count() > 0){
			Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','该任务包含子任务,不能删除!'));
		}else{
			//断开任务与user的连接/
			foreach(Task_Model::$roles as $role){
				foreach(Q("{$task} user.$role") as $user){
					$task->disconnect($user,$role);
				}
			}
			$parent_url = $task->parent->url(NULL, NULL, NULL, 'view');
			if($task->delete()){
				Lab::message(LAB::MESSAGE_NORMAL,I18N::T('projects','任务删除成功!'));
				URI::redirect($parent_url);
			}else{
				Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','任务删除失败!'));
			}
		}
		URI::redirect($_SESSION['system.current_layout_url']);
	}
	
}

class Task_AJAX_Controller extends AJAX_Controller{
	
	function index_approve_button_click($tid=0){
		$task = O('task', $tid);
		if (!$task->id) {
			JS::alert(I18N::T('projects', '任务不存在或者已经被删除！'));
			JS::refresh();
		}
		// 没有添加修改任务的权限 不是task的supervisor， 不能执行此操作
		$me = L('ME');
		if (!$me->access('添加/修改任务', $task)) {
			$roles = $task->user_roles($me);
			if(!in_array('parent_supervisor ', $roles)) {
				JS::alert(I18N::T('projects', 'Error 401: 您无权进行此操作，请联系管理员！'));
				return;
			}
		}

		$form = Form::filter(Input::form());
		if ($task->approved==$form['approved']) {
			JS::alert(I18N::T('projects', '任务已经处于该状态！'));
			return;
		}
		$confirm = $task->approved ? I18N::T('projects', '您确实要重启动该任务？') : I18N::T('projects', '您确实要结束该任务？');
		if (!JS::confirm($confirm)) {
			return;
		}
		//权限判断断之后
		$task->approved = !$task->approved;
		if ($task->save()) {
			$id = '#' . $form['id'];
			Output::$AJAX[$id] = [
				'data'=>(string)V('task/approve', ['task'=>$task]),
				'mode'=>'replace'
			];
		}
		else{
			JS::alert(I18N::T('projects', '任务状态更改失败！'));
		}
	}

	function index_task_update($tid=0){

		try {

			$form = Input::form();
	
			$task = O('task', $form['id']);
			if(!$task->id) {
				JS::alert(I18N::T('projects', '***不存在'));
				throw new Error_Exception;
			}

			// 如果有权拖动
			$me = L('ME');
			if (!$me->access('添加/修改任务', $task)) {
				$roles = $task->user_roles($me);
				if (!in_array('supervisor', $roles) && !in_array('parent_supervisor', $roles)) {
					JS::alert(I18N::T('projects', 'Error 401: 您无权进行此操作，请联系管理员！'));
					throw new Error_Exception;
				}
			}
			
			$dtstart = (int)$form['dtstart'];
			$dtend = (int)$form['dtend'];
			$changed = TRUE;
			// 如果可以拖动
			$res = $task->adjust($dtstart, $dtend);
			if (!is_array($res) || count($res)<1) {
				$changed = FALSE;
			}
			else {
				foreach($res as $t){
					$t->save();
				}
			}
		
			Output::$AJAX['moved'] = [
				'data' => [
					'moved' => $changed ? 1 : 0,
					'alert' => I18N::T('projects','任务时间不能超过任务集的时间!')
				]
			];
		}
		catch (Error_Exception $e) {
		}
		
	}

	function index_task_delete($tid=0){

	}

	function index_task_expand($tid=0){
		$form = Input::form();

		$task = O('task', $form['id']);
		if (!$task->id){
			JS::alert(I18N::T('projects', '***不存在'));
			return;
		}

		if ($task->type==Task_Model::TYPE_CONTAINER) {
			// task的attendees， 以及， parent的workers和supervisors可以查看
			$me = L('ME');
			if(!$me->access('查看所有任务')) {			
				$roles = $task->user_roles($me);
				if(
					!in_array('supervisor', $roles)
					&&
					!in_array('worker', $roles)
					&&
					!in_array('attendee', $roles)
					&&
					!in_array('parent_supervisor ', $roles)
				) {
					JS::alert(I18N::T('projects', 'Error 401: 您无权进行此操作，请联系管理员！'));
					return;
				}
			}
			
			$tasks = Q("task[parent={$task}]");
			$nodes = [];
			if(count($tasks)) foreach($tasks as $task){
				
				$nodes[] = [
					'id' => (int) $task->id,
					'url' => $task->url(),
					'title' => $task->name,
					'dtStart' => (int) $task->dtstart,
					'dtEnd' => (int) $task->dtend,
				];
				
			}
			
			Output::$AJAX['children'] = $nodes;	
		}
		
	}

	function index_task_contextmenu(){
		$form = Input::form();
		$id = $form['id'];
		$task = O('task', $id);
		if($task->id){
			$type = strtolower($form['type']);
			if ($type!='click') {
				$data = (string) V('timeline/contextmenu', ['task'=>$task]);
			}
			else {
				$data = (string) V('timeline/info', ['task'=>$task]);
			}
			Output::$AJAX['contextmenu'] = [
				'data'=>$data
			];	
		}else{
			Output::$AJAX['contextmenu'] = [
				'data'=>'error'
			];	
		}
	}

	function index_parent_select_change($op='add', $tid=0){
		$form = Form::filter(Input::form());
		$prev_uniqid = $form['prev_uniqid'];
		$date_box_id = $form['data_box_id'];
		// 如果parent不存在，从地址栏获取，parent
		if ($op=='add') {
			$parent_task = O('task', $tid);
		}
		elseif ($op=='edit') {
			$current = O('task', $tid);
			$parent_task = $task->parent;
		}
		// 获取ajax post的parent
		$parent = O('task', $form['parent']);
		$parent_task = $parent->id ? $parent : $parent_task;
		// 如果parent不存在，则出错提醒
		if(!$parent_task->id) {
			JS::alert(I18N::T('projects', '选中的父节点不存在或被删除，请重新选择。'));
		}
		else{
			//动态修改task的起止时间
			$dtstart = $current->id ? ( $current->prev->id ? $current->prev->dtend : $current->dtstart) : $parent_task->dtstart;
			$dtend = $current->id ?  ( $current->next->id ? $current->next->dtstart : $current->dtend) : $parent_task->dtend;
			$js = '$input=jQuery("input[name=dtstart]:first"); $input.val('.$dtstart.'); $input.prev().val("'.date('Y/m/d H:s A', $dtstart).'");';
			$js .= '$input=jQuery("input[name=dtend]:first"); $input.val('.$dtend.'); $input.prev().val("'.date('Y/m/d H:s A', $dtend).'");';
			JS::run($js);
			//task->prev->select
			Output::$AJAX['#'.$prev_uniqid] = [
				'data'=>(string) V('task/prev',['prev_uniqid' => $prev_uniqid, 'current' => $current, 'parent_task'=>$parent_task]),
				'mode'=>'replace',
			];
		}		
	}
	
	function index_prev_select_change(){
		$form = Form::filter(Input::form());
		$prev = O('task', $form['prev']);
		$dtstart = $prev->dtend;
		//动态修改task的start
		$js = '$input=jQuery("input[name=dtstart]:first"); $input.val('.$dtstart.'); $input.prev().val("'.date('Y/m/d H:s A', $dtstart).'");';
		JS::run($js);
	}
	
	function index_task_children() {
		$form = Form::filter(Input::form());
		$task = O('task', $form['id']);
		if (!$task->id){
			JS::alert(I18N::T('projects', '***不存在'));
			return;
		}
		$tasks = Q("task[parent={$task}]");
		if ($tasks->total_count() > 0) {
			$count = TRUE;
		}
		else {
			$count = FALSE;
		}
		Output::$AJAX['return_value'] = $count;
	}
}
