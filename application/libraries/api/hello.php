<?php

class API_Hello {
	
	function _default() {
		$args = func_get_args();
		return json_encode($args);
	}
}
