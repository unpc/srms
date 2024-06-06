<?php

class Project_Controller extends Base_Controller{
	
	function add(){
		$this->edit(0);
	}	

	function edit($pid=0){
		$project = O('project',$pid);
		$me = L('ME');
		// 新建 没有“添加/修改项目”权限 -> 出错
		// 编辑 （没有“添加/修改项目”权限）并且（不是项目的管理员）-出错
		if ($project->id){
			$roles = $project->task->user_roles($me);
			if (!$me->access('添加/修改项目', $project) && (!in_array('supervisor', $roles) && !in_array('parent_supervisor', $roles))) {
				URI::redirect('error/401');
			}
		}
		elseif (!$me->access('添加/修改项目', $project)) {
			URI::redirect('error/401');
		}
		
		if(Input::form('submit')){
			if($project->task->id){
				$task = $project->task;
			}
			else{
				$task = O('task');
				$project->task = $task;
			}
			$form = Form::filter(Input::form());
			$task->name = $form['name'];
			$task->description = $form['description'];
			$task->dtstart = $form['dtstart'];
			$task->dtend = $form['dtend'];
			$task->approved = $form['approved'];
			$task->type = Task_Model::TYPE_CONTAINER;
			$task->locked = $form['locked'] ? true : false;

			if($task->save() && $project->save()){
				//attendees
				$attendees = json_decode($form['attendees'], true);				
				if(!is_array($attendees))  $attendees = [];
				Task::replace_to_connection($project->task, array_keys($attendees), 'attendee');
				//workers
				$workers = json_decode($form['workers'], true);				
				if(!is_array($workers))  $workers = [];
				Task::replace_to_connection($project->task, array_keys($workers), 'worker');
				//supervisors
				//没有（添加/修改项目）权限，不能为项目指定负责人
				if($me->access('添加/修改项目', $project)){
					$supervisors = json_decode($form['supervisors'], true);
					if(!is_array($supervisors)) $supervisors = []; 
					Task::replace_to_connection($project->task, array_keys($supervisors), 'supervisor');
				}
				
				Lab::message(LAB::MESSAGE_NORMAL,I18N::T('projects','项目更新成功!'));
				URI::redirect($project->url(NULL, NULL, NULL, 'edit'));
			}else{
				Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','项目更新失败!'));
			}
		}
	
		$primary_tabs = $this->layout->body->primary_tabs;
		if($project->id){
			//如果 project_id 存在,则显示编辑项目tab
			$primary_tabs
				->add_tab('info',[
						'url'=>$project->task->url(),
						'title'=>I18N::T('projects','查看%project', ['%project'=>$project->task->name]),
					])
				->add_tab('edit',[
						'url'=>$project->url(NULL, NULL, NULL, 'edit'),
						'title'=>I18N::T('projects','编辑项目'),
					])
				->select('edit');
		}
		else {
			//如果 project_id 不存在,则显示添加项目tab
			$primary_tabs
				->add_tab('add',[
						'url'=>URI::url('!projects/project/add'),
						'title'=>I18N::T('projects','添加项目'),
					])
				->select('add');
		}
		
		$primary_tabs->content 
			= V('project/edit',[
									'project'=>$project,
									'form'=>$form,
								]);
	}
	
	function delete($pid=0){
		$project = O('project',$pid);
		$me = L('ME');
		if (!$project->id) {
			URI::redirect('error/404');
		}
		// 没有“添加/修改项目”权限 -> 出错
		if (!$me->access('添加/修改项目', $project)) {
			URI::redirect('error/401');
		}
		//该项目包含子项目不能删除
		if (Q("task[parent={$project->task->id}]")->total_count() > 0) {
			Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','该项目包含任务，不能删除!'));	
		}
		else {
			//断开任务与user的连接
			if ($project->task->id) {
				if(is_array(Task_Model::$roles)) foreach(Task_Model::$roles as $role){
					$tmp = Q("{$project->task} user.{$role}");
					foreach($tmp as $user){
						$project->disconnect($user,$role);
					}
				}
			}
			if($project->delete()){
				Lab::message(LAB::MESSAGE_NORMAL,I18N::T('projects','项目删除成功!'));
			}else{
				Lab::message(LAB::MESSAGE_ERROR,I18N::T('projects','项目删除失败!'));
			}
		}
		URI::redirect('!projects');
	}	

}
