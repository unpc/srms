<?php
class Auth extends _Auth {
	static function login($token) {
		parent::login($token);
	}
	
	static function logout() {
		parent::logout();
	}
}
