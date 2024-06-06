<?php
abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->layout->title = I18N::T('orders', '订单管理');
		$this->layout->body = V('body');
        $this->add_css('orders:order orders:common');
		$primary_tabs = Widget::factory('tabs');
		$me = L('ME');
		if ($me->is_allowed_to('列表', 'order')) {
			$primary_tabs
				->add_tab('orders', [
					'url'=>URI::url('!orders/index'),
					'title'=>I18N::T('orders', '订单列表'),
				]);
		}
		$this->layout->body->primary_tabs = $primary_tabs;
	}
}
