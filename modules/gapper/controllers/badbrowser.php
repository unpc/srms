<?php

class Badbrowser_Controller extends Layout_Controller {
	function index() {
		$this->layout->body = V('gapper:bad_browser');
	}
}