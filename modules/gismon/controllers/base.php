<?php

abstract class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		
		$this->layout->title = 'GIS监控';
		$this->layout->body = V('gismon:body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs
				->add_tab('buildings', [
						'url'=>URI::url('!gismon/buildings'),
						'title'=>I18N::T('gismon', '楼宇列表'),
					])
				->add_tab('map', [
						'url'=>URI::url('!gismon/map'),
						'title'=>I18N::T('gismon', 'GIS地图'),
					]);
		
		$this->add_css('gismon:common');
		
	}

}
