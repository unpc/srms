<?php

abstract class Base_Controller extends Layout_Controller {

	function _before_call($method, &$params) {

        if (People::perm_in_uno()){
            URI::redirect('error/404');
        }

		parent::_before_call($method, $params);
		
		$this->layout->title = I18N::T('roles', '权限管理');
		$this->layout->body = V('body');
		$primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs = $primary_tabs;
	}

}

