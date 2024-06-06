<?php

abstract class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs= Widget::factory('tabs');

		if(L('ME')->is_allowed_to('查看', 'user')){
			$this->layout->body->primary_tabs
				->tab_event('people.base.tab')
				->content_event('people.base.tab.content')
				->add_tab('all', [
					'url'=>URI::url('!people/list'),
					'title'=>I18N::T('people', '成员列表'),
					'weight'=>-2000
				]);
		}

		$this->add_css('people:common');
	}
	
}
