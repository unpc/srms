<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		
		$me = L('ME');
		$this->layout->title = I18N::T('messages', '消息中心');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs
				->add_tab('index', [
					'url' => URI::url('!messages/index'),
					'title' => I18N::T('messages', '消息中心'),
				]);

		if ($me->id && $me->is_active() && Config::get('messages.add_message.switch_on', TRUE) ) {
            if (!Event::trigger('cannot.add.message')) {
                $this->layout->body->primary_tabs
                        ->add_tab('add', [
                            'url' => Event::trigger('db_sync.transfer_to_master_url', "!messages/add") ?: URI::url('!messages/add'),
                            'title' => I18N::T('messages', '添加消息'),
                        ]);
            }
		}

		$this->add_css('messages:message');
	}

}
