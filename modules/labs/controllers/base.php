<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {
		parent::_before_call($method, $params);
		$this->layout->body = V('body');
		$me = L('ME');

        $this->layout->title = I18N::T('equipments', '课题组');

		$this->layout->body->primary_tabs = Widget::factory('tabs');

		if ($me->is_allowed_to('查看', 'lab')) {
		    $this->layout->body->primary_tabs
                ->add_tab('all', [
                    'url'=>URI::url('!labs/list'),
                    'title'=>I18N::T('labs', '实验室目录'),
                ]);
		}
	}
}
