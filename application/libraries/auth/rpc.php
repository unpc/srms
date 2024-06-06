<?php

class Auth_RPC implements Auth_Handler {
	private $_rpc;
	private $_backend;

    function __construct(array $opt){

		$url = $opt['rpc.url'];

		$this->_rpc = new RPC($url);
		$this->_backend = $opt['backend'];
    }
    //验证令牌/密码
    function verify($token, $password) {

		// $ntoken = strtr($token, ':', '|');  // genee:ids.nankai.edu.cn : 被用于做嵌套替换
		$ntoken = preg_replace('/[\|:].*$/', '', $token);


		if ($this->_backend) {
			$rpc_token = $ntoken .'|'.$this->_backend;
		}

        //rpc->auth之前先认证
        $this->_rpc = Event::trigger('auth.rpc.authorize') ? : $this->_rpc;
		//去除backend后面的%source
	    $rpc_token = preg_replace('/%[^%]+$/', '', $rpc_token);
	    
		$key = $this->_rpc->auth->verify($rpc_token, $password);

		if ($key) {
			$_SESSION['#RPC_TOKEN_KEY'][$this->_backend][$ntoken] = $key;
			return TRUE;
		}

		return FALSE;
    }
    //设置令牌
    function change_token($token, $new_token) {
        //安全问题 禁用
        return FALSE;
    }
    //设置密码
    function change_password($token, $password) {
        //安全问题 禁用
        return FALSE;
    }
    //添加令牌/密码对
    function add($token, $password) {
        //安全问题 禁用
        return FALSE;
    }
    //删除令牌/密码对
    function remove($token) {
        //安全问题 禁用
        return FALSE;

	}

	static function get_user_info($token) {
        list($token, $backend) = Auth::parse_token($token); //把backend (一般是RPC)与token剥离
		$key = $_SESSION['#RPC_TOKEN_KEY'][$backend][$token];
		$opts = (array) Config::get('auth.backends');
		if ($opts[$backend]['handler'] == 'rpc') {
			$rpc = new RPC($opts[$backend]['rpc.url']);
			return $rpc->auth->get_user_info($key);
		}

		return [];
	}

}


