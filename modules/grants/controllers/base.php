<?php

abstract class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params) {
		
		parent::_before_call($method, $params);
		
		$this->layout->title = I18N::T('grants','经费管理');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');

		$this->layout->body->primary_tabs
			->add_tab('list', [
				'url'=>URI::url('!grants/grants'),
				'title'=>I18N::T('grants', '经费管理'),
			])
			;

	}
}
