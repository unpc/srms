<?php 
class CLI_Meeting{
	static function delete_expire_auth() {
		$now = time();
		$auths = Q("um_auth[atime=1~{$now}]");
		foreach ($auths as $auth) {
			$auth->delete();
		}
	}
}