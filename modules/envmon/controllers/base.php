<?php

abstract class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params){
		
		parent::_before_call($method, $params);
		
		$this->layout->title = I18N::T('envmon', '环境监控');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		
		$this->layout->body->primary_tabs
			->add_tab('list', [
					'url'=>URI::url('!envmon/index'),
					'title'=>I18N::T('envmon', '监控对象'),
				]);

		$this->layout->body->primary_tabs
				->tab_event('envmon.primary.tab', $params)
				->content_event('envmon.primary.content', $params);

		$this->add_css('envmon:common');

	}
}
