<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		
		$this->layout->title = I18N::T('billing', '财务管理');
		$this->layout->body = V('body');
		$me=L('ME');
		$primary_tabs = Widget::factory('tabs');
		#ifndef (billing.single_department)
		if (!$GLOBALS['preload']['billing.single_department']) {
			/*
			NO.TASK#300(guoping.zhang@2010.12.10)
			财务管理模块权限设置
			*/
			if ($me->is_allowed_to('列表', 'billing_department')) {
				$primary_tabs
					->add_tab('departments', [
						'url'=>URI::url('!billing/departments'),
						'title'=>I18N::T('billing', '财务部门列表'),
						]);
			}
		}
		#endif
		$this->layout->body->primary_tabs = $primary_tabs;
		$this->add_css('billing:common');

	}

}

