<?php

/*错误代号
    请求来源非法! 1000
*/

class API_SMS {

	function send() {

        $this->_check();

		$args = func_get_args();
		return call_user_func_array('SMS::send', $args);
	}

    //判断是否可进行SMS发送
    private function _check() {

        if (! in_array($_SERVER['REMOTE_ADDR'], Config::get('api.sms')['remote_addresses'])) {
            throw new API_Exception('请求来源非法!', 1000);
        }
    }
}
