<?php

class Auth_LoGapper implements Auth_Handler {

       private $_logapper = null;
       private $_backend = null;
    function __construct(array $opt){
               $this->_logapper = new LoGapper();
               $this->_backend = $opt['backend'];
       }

    //验证令牌/密码
    function verify($token, $password) {
               //$cache = Cache::factory('redis');
               $result = $this->_logapper->post('auth/user-token', [
                       'username'=> $token,
                       'password'=> $password,
                       'backend'=> $this->_backend
               ], true);
               if (!$result) return false;
               if (!$result['access_token']) return false;
               LoGapper::cacheUserOAuthToken(Auth::make_token($token, $this->_backend), $result['access_token'], $result['expires_in'], $result['refresh_token']);

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
        return TRUE;
    }
    //删除令牌/密码对
    function remove($token) {
        //安全问题 禁用
        return FALSE;

       }
}
