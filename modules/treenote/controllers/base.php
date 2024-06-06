<?php

class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->layout->title = I18N::T('treenote','工作管理');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		
		$this->layout->body->primary_tabs
			->add_tab('work', [
					'url'=>URI::url('!treenote/work'),
					'title'=>I18N::T('treenote', '我的工作'),
				])
			->add_tab('projects', [
					'url'=>URI::url('!treenote/projects'),
					'title'=>I18N::T('treenote', '项目列表'),
				])
			;
				
		$this->layout->body->primary_tabs
				->tab_event('treenote.primary.tab')
				->content_event('treenote.primary.content');

		$this->add_css('treenote:common');
	}
}
