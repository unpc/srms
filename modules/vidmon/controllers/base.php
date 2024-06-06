<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		$this->layout->title = I18N::T('vidmon', '视频监控');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs
			->add_tab('list', [
				'url' => URI::url('!vidmon/list'),
				'title' => I18N::T('vidmon', '视频列表')
			]);

        $me = L('ME');

        if ($me->is_allowed_to('多屏监控', 'vidcam')) {
            $this->layout->body->primary_tabs
                ->add_tab('monitor', [
                    'url' => URI::url('!vidmon/monitor'),
                    'title' => I18N::T('vidmon', '多屏监控')
                ]);
        }

		$this->add_css('vidmon:common');
	}
}
