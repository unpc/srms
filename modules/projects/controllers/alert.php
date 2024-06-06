<?php

class Alert_Controller extends Base_Controller{
	
	function index($tid=0, $type=NULL){
		$task = O('task', $tid);
		if(!$task->id) URI::redirect('error/404');
		
		$form = Form::filter(Input::form());
		if (isset($form['submit']) && $form->no_error) {
			$tmps = json_decode($form['users'], true);
			if (count($tmps)) {
				$errs = [];
				foreach($tmps as $k=>$v){
					$u = O('user', $k);
					if (!$u->id) {
						$errs[$k]=$v;
					}
					else {
						$subject = $form['subject'];
						$body = $form['content'];
						$me = L('ME');
						
						//是否需要判断模块开通状态？
						$message = O('message');
						$message->sender = $me;	
						$message->receiver = $u;
						$message->title = $subject;
						$message->body = $body;
						$message->save();
						
						$email = new Email($me);
						$email->to($u->email);
						$email->subject($subject);
						$email->body($body);
						$email->send();
					}
				}
				if (count($errs)) {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('projects', '为以下用户发送Notification失败：%err', ['%err'=>implode(', ', $errs)]));
				}
				URI::redirect($task->url(NULL, NULL, NULL, 'view'));
			}
		}
		
		if(in_array($type, Task_Model::$roles)){
			$users = Q("{$task} user." . $type);
			if (count($users)) {
				$users = $users->to_assoc();
			}
		}
		if(!is_array($users)) {
			$users = [];
		}
		
		$uid = $form['uid'];
		if ($uid) {
			$user = O('user', $uid);
			if($user->id) $users[$user->id] = $user->name;
		}
		
		$primary_tabs = $this->layout->body->primary_tabs;

		$project = $task->project();
		$primary_tabs->add_tab('project',[
			'url'=> $project->task->url(NULL, NULL, NULL, 'index'),
			'title'=>I18N::T('projects','项目：%project', ['%project'=>$project->task->name]),
		])->add_tab('task',[
			'url'=> $task->url(NULL, NULL, NULL, ($task->type==Task_Model::TYPE_CONTAINER) ? 'index' : 'info'),
			'title'=>I18N::T('projects','任务：%task', ['%task'=>$task->name]),
		])->add_tab('alert', [
			'url'=> $task->url(NULL, $_GET, NULL, 'alert'),
			'title'=>I18N::T('projects','消息'),
		])->select('alert');		
		
		$primary_tabs->content = V('alert/form', [
			'users'=>$users,
			'form'=>$form
		]);
	}
}
