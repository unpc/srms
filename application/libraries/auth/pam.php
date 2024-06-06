<?php
/*
sample auth config:

$config['backends']['pam'] = array(
	'title'=>'PAM',
	'handler'=>'pam',
	'readonly' => TRUE,
	'allow_create' => FALSE,
);

参考: http://pecl.php.net/package/PAM

该方法是为解决 YMKK 做 Windows AD 验证写的, 现在他们试用结束了,
暂时无人用该方法, 所以注释掉, 直接返回 false
(xiaopei.li@2013-05-27)

*/
class Auth_PAM implements Auth_Handler {

	function __construct(array $opt) {

	}

	function verify($token, $password) {
		return FALSE;
		

		/*
			此处不做 function_exists 检查,
			因为这样更容易发现错误

			if ( !function_exists( 'pam_auth' ) ) {
				error_log('pam_auth() not exists!');
				return;
			}
		*/

		/*
		$ret = FALSE;

		// php 5.4 中 "Call-time pass-by-reference has been removed"
		if ( pam_auth($token, $password, &$error) ) {
			$ret = TRUE;
		}
		else {
			error_log( "$token pam_auth() errors: $error" );
		}

		return $ret;
		*/
	}

	function change_token($token, $new_token) { return FALSE; }

	function change_password($token, $password) { return FALSE; }

	function add($token, $password) { return FALSE; }

	function remove($token) { return FALSE; }

}