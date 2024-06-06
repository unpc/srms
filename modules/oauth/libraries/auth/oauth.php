<?php
class Auth_OAuth implements Auth_Handler {

	function __construct(array $opt) { }

	function verify($token, $password) {
		Lab::message(Lab::MESSAGE_ERROR, HT('请使用登录链接登录!'));
		return FALSE;
	}

	function change_token($token, $new_token) { return FALSE; }

	function change_password($token, $password) { return FALSE; }

	function add($token, $password) { return FALSE; }

	function remove($token) { return FALSE; }

}
