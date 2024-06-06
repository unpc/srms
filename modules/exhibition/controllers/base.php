<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {

		parent::_before_call($method, $params);

		$me = L('ME');
		
        $this->layout->title = I18N::T('exhibition', '展会管理');
        
        $this->layout->body = V('body');
        
        $this->layout->body->primary_tabs = Widget::factory('tabs');
        
		if (in_array($me->token, Config::get('lab.admin'))) {
			$this->layout->body->primary_tabs
				->add_tab('statistics', [
					'url' => URI::url('!exhibition/index/statistics'),
					'title' => I18N::T('exhibition', '仪器设备'),
				])
				->add_tab('similarity', [
					'url' => URI::url('!exhibition/index/similarity'),
					'title' => I18N::T('exhibition', '同类仪器'),
				])
				->add_tab('forecast', [
					'url' => URI::url('!exhibition/index/forecast'),
					'title' => I18N::T('exhibition', '预测仪器'),
                ]);
		}
	}
}
