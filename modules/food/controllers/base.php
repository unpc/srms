<?php

abstract class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		
		$this->layout->title = I18N::T('food', '点餐系统');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');

		$this->layout->body->primary_tabs
					->add_tab('index', [
						'url' => URI::url('!food'),
						'title' => I18N::T('food', '订餐'),
					])
					->add_tab('fd_list', [
						'url' => URI::url('!food/food'),
						'title' => I18N::T('food', '菜式列表')
					])
					->add_tab('fd_order', [
						'url' => URI::url('!food/fd_order'),
						'title' => I18N::T('food', '订餐统计')
					]);
	}
	
}
