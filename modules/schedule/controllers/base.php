<?php

class Base_Controller extends Layout_Controller {
	
	function _before_call($method, &$params){
		
		parent::_before_call($method, $params);
		$this->layout->body = V('body');
	}

}
		

