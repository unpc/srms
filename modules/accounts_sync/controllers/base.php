<?php

abstract class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs= Widget::factory('tabs');
		$this->layout->body->primary_tabs 
			->add_tab('list', [
				'url'=>URI::url('!accounts'),
				'title'=>I18N::T('accounts', 'LIMS客户列表'),
			]);
		$this->add_css('accounts:common');
	}
	
}
