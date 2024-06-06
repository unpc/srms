<?php

class Auth_CAS implements Auth_Handler {

	//用于存储cookie_file对象
    private $_cookie_file;

    private $_http;

    private $_opt;
	
	function __construct(array $opt) {
        $this->_http = HTTP::instance();
        $cookie_file = new cookie_file;
        $this->_cookie_file = $cookie_file->path;
        $this->_opt = $opt;
	}

    //验证令牌/密码
    function verify($token, $password) {
        $this->_http->cookie_file($this->_cookie_file);
        $response = $this->_http->request($this->_opt['cas.login_url'], 5);

        preg_match('/<input[^>]*name=\"lt\"[^>]*value=\"(.*)\"[^>]*>/', $response->body, $matchs);
        $lt = $matchs[1];

        $post_data = array_merge([
            'lt' => $lt,
            '_eventId' => 'submit',
            'username' => $token,
            'password' => $password,
            'submit' => T('登录')
        ], (array)Event::trigger('Auth_CAS.get_post_data', 
            $response->body, 
            $token, 
            $password
        ));

        $this->_http->cookie_file($this->_cookie_file);
        $response = $this->_http->post($post_data)->request($this->_opt['cas.login_url'], 5);
        
        $ticket = $this->_opt['cas.ticket'] ?: 'CASTGC';

        $cookies = $this->_http->cookie();

        $cookies = Event::trigger('Auth_CAS.get_http_cookie', $this->_http, $this->_cookie_file) ? : $cookies;

        return $cookies[$ticket] ? TRUE : FALSE;
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

}


