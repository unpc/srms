<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		
		$this->layout->title = I18N::T('labnotes', '实验记录');
		$this->layout->body = V('body');
		$me=L('ME');
		$primary_tabs = Widget::factory('tabs');
		$primary_tabs
				->add_tab('my_note', [
					'url'=>URI::url('!labnotes/'),
					'title'=>I18N::T('labnotes', '我的实验记录'),
				])
				->add_tab('All_note', [
					'url'=>URI::url('!labnotes/all_note'),
					'title'=>I18N::T('labnotes', '所有实验记录'),
				]);

		$this->layout->body->primary_tabs = $primary_tabs;

	}

}

