<?php

class Error_Controller extends _Error_Controller {
	
	function index($code = 404) {
		switch ($code) {
		case 401:
			if (!$_SESSION['#LOGIN_REFERER']) {
				$_SESSION['#LOGIN_REFERER'] = $_SESSION['HTTP_REFERER'];
			}
			break;
		case 429:
			if (!$_SESSION['#LOGIN_REFERER']) {
				$_SESSION['#LOGIN_REFERER'] = URI::url();
			}
			header($_SERVER["SERVER_PROTOCOL"]." 429 Too Many Requests");
			header("Status: 429 Too Many Requests");
			break;
		}
		parent::index($code);
	}
	
}

