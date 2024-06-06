<?php

class Project_Model extends Presentable_Model{

	protected $object_page = [
		'edit' => '!projects/project/edit.%id[.%arguments]',
		'delete' => '!projects/project/delete.%id',
		'add' => '!projects/project/add',
		'info'=>'!projects/project/info.%id',
	];
	
	/*
	const DELETABLE = 1; // task 是 可删除的
	const UNDELETABLE = 0; // task 是 不可删除的
	
	const UNLOCKED  = 0; // 未锁定
	const LOCKED = 1; // 已锁定
	*/
	
	function connect($object, $type=NULL, $approved=FALSE){
		if($this->id){
			$this->task->connect($object, $type, $approved);
		}
	}
	
	function disconnect($object, $type=NULL, $approved=FALSE){
		if($this->id){
			$this->task->disconnect($object, $type , $approved);
		}
	}
	
	function delete(){
		if($this->id){
			$this->task->delete();
			return parent::delete();
		}
	}

	function & links() {
		$links = new ArrayIterator;		
		
		$me = L('ME');
		$task = $this->task;
		$supervisors = Q("{$task} user.supervisor")->to_assoc('id', 'id');
		if (in_array($me->id, $supervisors) || $me->access('添加/修改项目', $this)) {
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
		}
	
		return (array) $links;
	}
	
	
}
