<?php

abstract class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		
		$this->layout->title = I18N::T('resume', '简历记录');
		$this->layout->body = V('body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		
		$me = L('ME');

		if( $me->is_allowed_to('查看', 'resume') ){
			$this->layout->body->primary_tabs
				->add_tab('resume', [
					'url' => URI::url('!resume'),
					'title' => I18N::T('resume', '简历记录')
				]);
		}

		if( $me->is_allowed_to('查看', 'position') ){
			$this->layout->body->primary_tabs
				->add_tab('position', [
					'url' => URI::url('!resume/position'),
					'title' => I18N::T('resume', '职位列表')
				]);
		}

		$this->add_css('resume:common');
	}
}
