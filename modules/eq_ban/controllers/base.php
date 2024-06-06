<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {

		parent::_before_call($method, $params);

		$me = L('ME');
		
		$this->layout->title = I18N::T('eq_ban', '黑名单管理');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		if ($me->is_allowed_to('查看全局', 'eq_banned')) {
			$this->layout->body->primary_tabs
				->add_tab('admin', [
					'url' => URI::url('!eq_ban/index.admin'),
					'title' => I18N::T('eq_ban', '黑名单'),
				]);
		}
		if ($me->is_allowed_to('查看机构', 'eq_banned')) {
			$this->layout->body->primary_tabs
				->add_tab('group', [
					'url' => URI::url('!eq_ban/index.group'),
					'title' => I18N::T('eq_ban', '平台黑名单'),
				]);
		}
		if ($me->is_allowed_to('查看仪器', 'eq_banned')) {
			$this->layout->body->primary_tabs
				->add_tab('eqs', [
					'url' => URI::url('!eq_ban/index.eqs'),
					'title' => I18N::T('eq_ban', '仪器黑名单'),
				]);
		}

		if ($me->is_allowed_to('查看仪器', 'eq_banned')) {
			$this->layout->body->primary_tabs
			->add_tab('eq_violation', [
				'url' => URI::url('!eq_ban/eq_violation'),
				'title' => I18N::T('eq_ban', '仪器违规行为'),
			]);
		}

		if ($me->is_allowed_to('查看违规记录', 'eq_banned')) {
			$this->layout->body->primary_tabs
			->add_tab('violation', [
				'url' => URI::url('!eq_ban/violation'),
				'title' => I18N::T('eq_ban', '违规记录'),
			]);
		}
		
	}
}
