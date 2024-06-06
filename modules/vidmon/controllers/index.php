<?php

class Index_Controller extends Layout_Controller {
	function index() {
		URI::redirect('!vidmon/list');
	}
}
