<?php

class Session extends _Session {

	static $url_key;
	static $module_key;
	static $cleaned_others = FALSE;

	static function setup(){
		
		parent::setup();
		
		//去掉route之后的参数
		$url = Input::route();
		$url = preg_replace('/\..+$/', '', $url);
		
		self::$url_key = $url_key = 'URL:'.md5($url);

		$module_key = 'MODULE:'.md5($url);
		$_SESSION[$module_key] = $url ? explode('/', $url)[0] : "";
		self::$module_key = $module_key;
	}
	
	static function cleanup_other_urls() {
		
		$url_key = self::$url_key;
		$module_key = self::$module_key;

		if (is_array($_SESSION)) foreach ($_SESSION as $k=>&$v) {
			if (preg_match('/^URL:/', $k)) {
				$module_k =str_replace('URL:', 'MODULE:', $k);
				if (!$_SESSION[$module_key] 
					|| ($_SESSION[$module_key] && $_SESSION[$module_key] != $_SESSION[$module_k])) {
					if ($k != $url_key) {
						unset($_SESSION[$k]);
					}
				}
			}
		}
		
		self::$cleaned_others = TRUE;

	}

	static function get_url_specific($name, $default=NULL) {
		if (!self::$cleaned_others) {
			self::cleanup_other_urls();
		}
		
		$retval = $_SESSION[self::$url_key][$name];
		if ($retval === NULL) return $default;

		return $retval;
	}
	
	static function set_url_specific($name, $value) {

		if (!self::$cleaned_others) {
			self::cleanup_other_urls();
		}

		$url_key = self::$url_key;
		if ($value === NULL) {
			unset($_SESSION[$url_key][$name]);
		}
		else {
			$_SESSION[$url_key][$name] = $value;
		}
	}

}
