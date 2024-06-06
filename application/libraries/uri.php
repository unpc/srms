<?php

class URI extends _URI {
	
	static function redirect($url='', $query=NULL) {
		if (Lab::$messages) {
			$_SESSION['system.unlisted_messages'] = Lab::$messages;
		}
		$_SESSION['HTTP_REFERER'] = self::url();
		parent::redirect($url, $query);
	}

}
