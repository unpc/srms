<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		
		$me = L('ME');

		if (!$me->is_allowed_to('管理', 'cers')) {
			URI::redirect('error/401');
		}

		$this->layout->title = I18N::T('cers', 'CERS接口');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs')
				->add_tab('index', [
					'url' => URI::url('!cers/index'),
					'title' => I18N::T('cers', 'CERS接口')
				]);
		$this->add_css('cers:cers');
	}

}