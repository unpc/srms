<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {

		parent::_before_call($method, $params);

		$me = L('ME');
		
		$this->layout->title = I18N::T('notice', '公播管理');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs
            ->add_tab('material', [
                'url' => URI::url('!notice/play.material'),
                'title' => I18N::T('notice', '素材中心'),
            ])
            ->add_tab('list', [
                'url' => URI::url('!notice/play.list'),
                'title' => I18N::T('notice', '播单管理'),
            ])
            ->add_tab('approval', [
                'url' => URI::url('!notice/play.approval'),
                'title' => I18N::T('notice', '审核管理'),
            ]);
	}

}
