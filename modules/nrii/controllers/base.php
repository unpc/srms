<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {

		parent::_before_call($method, $params);

		$me = L('ME');
		
		$this->layout->title = I18N::T('nrii', '国家科技部平台对接');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		if ($me->is_allowed_to('管理', 'nrii')) {
			$this->layout->body->primary_tabs
				->add_tab('device', [
					'url' => URI::url('!nrii/nrii.device'),
					'title' => I18N::T('nrii', '大型科学装置'),
				])
				->add_tab('center', [
					'url' => URI::url('!nrii/nrii.center'),
					'title' => I18N::T('nrii', '科学仪器中心'),
				// 科学仪器服务单元 被国家科技部改版去掉了
				// ])
				// ->add_tab('unit', [
				// 	'url' => URI::url('!nrii/nrii.unit'),
				// 	'title' => I18N::T('nrii', '科学仪器服务单元'),
				]);
		}

		$this->layout->body->primary_tabs
				->add_tab('equipment', [
					'url' => URI::url('!nrii/nrii.equipment'),
					'title' => I18N::T('nrii', '单台套科学仪器设备'),
				]);

		if ($me->is_allowed_to('管理', 'nrii')) {
			// 服务成效 被国家科技部改版去掉了
			$this->layout->body->primary_tabs
				// ->add_tab('service', [
				// 	'url' => URI::url('!nrii/nrii.service'),
				// 	'title' => I18N::T('nrii', '服务成效'),
				// ])
				->add_tab('record', [
					'url' => URI::url('!nrii/nrii.record'),
					'title' => I18N::T('nrii', '服务记录'),
				]);
		}
				


		$this->add_css("nrii:common");
	}

}
