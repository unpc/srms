<?php

class Auth_Gateway implements Auth_Handler {

    function __construct(array $opt){}

    //验证令牌/密码
    function verify($token, $password) {
        list($identity, $source) = Auth::parse_token($token);
        $server = Config::get('gateway.server');
        $result = Gateway::verifyAuth([
            "username" => "$identity", 
            "password" => $password,
            'client_id'     => $server['params']['client_id'],
            'client_secret' => $server['params']['client_secret'],
        ]);
        if (!$result) return false;
        if (!$result['access_token']) return false;
        return true;
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


