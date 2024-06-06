<?php

class JS extends _JS {

	static function redirect($url='') {
		if (Lab::$messages) {
			$_SESSION['system.unlisted_messages'] = Lab::$messages;
		}
		parent::redirect($url);
	}
	
	static function refresh($selector='') {
		if (Lab::$messages) {
			$_SESSION['system.unlisted_messages'] = Lab::$messages;
		}
		parent::refresh($selector);
	}
}
