<?php

class Task {
	static function on_task_saved($e, $task, $old, $new){
		$o = $old->approved;
		$n = $new['approved'];
		// 修改了 task 的状态
		// task 完成， 如果该task是container, 则修改其children的状态未完成
		if ($o != $n && $n && $task->type==Task_Model::TYPE_CONTAINER) {
			$children = Q("task[parent={$task}][!approved]");
			if (count($children)) {
				foreach ($children as $child) {
					$child->approved = $n;
					$child->save();
				}
			}
		}
	}
	
	static function replace_to_connection($task, $users, $relation=NULL, $relations=['attendee', 'worker', 'supervisor']) {
		
		if (is_null($relation) || !in_array($relation, $relations)) return;
		$original_users = array_keys(Q("{$task} user.{$relation}")->to_assoc());
		
		$added_users = array_diff($users, $original_users);
		$deleted_users = array_diff($original_users, $users);
		
		if ($relation=='*') $relation='';
		
		//添加新的关系
		foreach($added_users as $id){
			$user = O('user',$id);
			if ($user->id) {
				$task->connect($user, $relation);
			}
		}
		//解除关系
		foreach($deleted_users as $id){
			$user = O('user',$id);
			if ($user->id) {
				$task->disconnect($user, $relation);
			}
		}
		
		$original_relations = $relations;
		unset($relations[$relation]);
		
		$str = implode('|', $relations);
		
		//如果某用户已经不再当前task的工作团队中，那么，该task的后代级联解除与该用户的关系
		$team = array_keys(Q("{$task} user.@({$str})")->to_assoc());
		$diff_users = array_diff($deleted_users, $team);
		if (count($diff_users)>0) {
			$children = $task->children();
			if(count($children)>0) foreach($children as $tid) {
				$t = O('task', $tid);
				if ($t->id) {
					foreach ($diff_users as $uid) {
						$u = O('user', $uid);
						foreach($original_relations as $r) {
							$t->disconnect($u, $r);
						}
					}
				}
			}
		}
		
	}
	
	static function before_labnote_edit($e, $note) {
		$e->return_value = '<input class="button" value="关联项目任务" />';
	}
}
