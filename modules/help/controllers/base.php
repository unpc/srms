<?php

abstract class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params){
		parent::_before_call($method, $params);

		$this->layout->body=V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs
			->add_tab('index', [
					'url'=>URI::url('!help/index'),
					'title'=>I18N::T('help', '帮助中心'),
				]);

	}

}
