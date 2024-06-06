<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {	
		parent::_before_call($method, $params);
		$me = L('ME');
		$this->layout->title = I18N::T('eq_meter', 'eq_meter');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs')
							->add_tab('index', [
								'url' => URI::url('!meeting/index'),
								'title' => I18N::T('meeting', 'Gmeter列表'),
							]);
	}
}