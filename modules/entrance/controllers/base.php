<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		$this->layout->title = I18N::T('entrance','门禁系统');
		$this->layout->body = V('body');
		$primary_tabs = $this->layout->body->primary_tabs = Widget::factory('tabs');
		$primary_tabs
				->add_tab('index',[
						'url'=>URI::url('!entrance/index'),
						'title'=>I18N::T('entrance','门禁列表'),
					]);
		$me = L('ME');
		if ($me->access('管理所有门禁') || $me->access('查看所有门禁的进出记录') || $me->access('查看负责仪器关联的进出记录')) {
			$primary_tabs->add_tab('record',[
						'url'=>URI::url('!entrance/dc_record/index'),
						'title'=>I18N::T('entrance','进出记录'),
					]);
		}
		

	} 
}
