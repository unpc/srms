<?php

abstract class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		$this->layout->title = I18N::T('technical_service', '技术服务');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs= Widget::factory('tabs');
		$this->layout->body->primary_tabs
			->tab_event('service.primary.tab', $params)
			->content_event('service.primary.content', $params);
        $this->add_css('technical_service:base');
		$this->add_css('technical_service:select2.min');
		$this->add_js('technical_service:select2.min');
        $this->add_js('technical_service:autocomplete.select2');
	}

}
