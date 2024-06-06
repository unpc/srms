<?php

class Index_Controller extends Base_Controller {
	
	function index() {
		$user = L('ME');

		/*
		if (!$user->is_allowed_to('列表', 'meeting')) {
			URI::url('error/404');
		}
		*/

		$selector = 'eq_meter';

	}
}