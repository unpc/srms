<?php
abstract class Base_Controller extends Layout_Controller {
	function _before_call($method, &$params) {
		if (!Config::get('wordpress.wp_base')) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('wordpress', 'WordPress 站点地址未配置, 请联系系统管理员'));
			URI::redirect('/');
		}

		parent::_before_call($method, $params);

		$this->layout->title = I18N::T('wordpress', 'Wordpress');
		$this->layout->body = V('body');
		$me = L('ME');
		$primary_tabs = Widget::factory('tabs');
		$primary_tabs
				->add_tab('public', [
					'url'=>URI::url('!wordpress/'),
					'title'=>I18N::T('wordpress', 'Wordpress'),
				]);

		$this->layout->body->primary_tabs = $primary_tabs;
		$this->add_css('wordpress:common');

	}
}
