<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');

		$this->layout->body->primary_tabs
			->tab_event('lab_projects.list.tab')
			->add_tab('list', [
				'url'=>URI::url('!lab_project/list'),
				'title'=>I18N::T('lab_project', '项目列表'),
			]);
	}
}
