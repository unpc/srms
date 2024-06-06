<?php

class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->layout->title = I18N::T('workflows','工作流程');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		
		$this->layout->body->primary_tabs
			->add_tab('index', [
					'url'=>URI::url('!workflows/index'),
					'title'=>I18N::T('workflows', '流程列表'),
				])
			->add_tab('projects', [
					'url'=>URI::url('!workflows/projects'),
					'title'=>I18N::T('workflows', '项目列表'),
				])
			;

		$this->layout->body->primary_tabs
				->tab_event('workflows.primary.tab')
				->content_event('workflows.primary.content');

		$this->add_css('workflows:common');

	}

}
