<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		
		$me = L('ME');

		$this->layout->title = I18N::T('announces', '系统公告');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs')
				->add_tab('index', [
					'url' => URI::url('!announces/index'),
					'title' => I18N::T('announces', '系统公告'),
				]);
		if($me->is_allowed_to('查看所有', 'announce')){
			$this->layout->body->primary_tabs
					->add_tab('all', [
						'url' => URI::url('!announces/all'),
						'title' => I18N::T('announces', '所有公告'),
					]);
		}
		if($me->is_allowed_to('添加', 'announce')){
			$this->layout->body->primary_tabs
					->add_tab('add', [
						'url' => URI::url('!announces/add'),
						'title' => I18N::T('announces', '添加公告'),
					]);
		}

        $this->layout->body->primary_tabs
                ->tab_event('announces.primary.tab', $params)
                ->content_event('announces.primary.content', $params)
                ->tool_event('announces.primary.tool_box');

        $this->add_css('preview');
        $this->add_js('preview');
        $this->add_css('announces:announce');
				
	}

}
