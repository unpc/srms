<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs
			->add_tab('all', [
				'url'=>URI::url('!people/list'),
				'title'=>I18N::T('people', '成员列表')
			])
			->add_tab('staff',[
				'url'=>URI::url('!staff/list'),
				'title'=>I18N::T('staff', '人事信息')
			])
			->select('staff');

	}
}
