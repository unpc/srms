<?php

class Base_Controller extends Layout_Controller{
	
	function _before_call($method, &$params){
		parent::_before_call($method, $params);

		$this->layout->title = I18N::T('projects', '项目管理');
		$this->layout->body = V('body');
		
		$primary_tabs = Widget::factory('tabs');

		$primary_tabs
				->add_tab('index', [
					'url'=>URI::url('!projects/index'),
					'title'=>I18N::T('projects', '项目列表'),
				])
				->add_tab('todo', [
					'url'=>URI::url('!projects/todo'),
					'title'=>I18N::T('projects', '我的工作列表'),
				]);
				
		$this->layout->body->primary_tabs = $primary_tabs;
	}
}
