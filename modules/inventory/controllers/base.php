<?php
abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);

		$this->add_css('inventory:base inventory:common');
		$this->add_js('inventory:barcode');
		$this->add_js('inventory:stocks');

		$this->layout->title = I18N::T('inventory', '存货管理');
		$this->layout->body = V('body');
		$primary_tabs = Widget::factory('tabs');
		$me = L('ME');
		if ($me->is_allowed_to('列表', 'stock')) {
			$primary_tabs
				->add_tab('stocks', [
					'url'=>URI::url('!inventory/index'),
					'title'=>I18N::T('inventory', '存货列表'),
				]);
		}

		$this->layout->body->primary_tabs = $primary_tabs;
	}
}
