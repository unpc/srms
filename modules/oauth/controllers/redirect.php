<?php
class Redirect_Controller extends _Layout_Controller {
	function success(){
		$this->layout = V('oauth:success');
	}

	function fail(){
		$this->layout = V('oauth:fail');
	}
}