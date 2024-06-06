<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {	
		parent::_before_call($method, $params);
		$me = L('ME');
		$this->layout->title = I18N::T('happy', '休闲时刻');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs')//system下的		
							->add_tab('index', [
								'url' => URI::url('!happy/index'),
								'title' => I18N::T('happy', '活动安排'),
							])
				   			->add_tab('history', [
								'url' => URI::url('!happy/history'),
								'title' => I18N::T('happy', '我的记录'),
								'weight' => 100,
							]);	
	}
}
