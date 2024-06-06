<?php

class Auth_Gapper implements Auth_Handler {

    function __construct(array $opt){}

    //验证令牌/密码
    function verify($token, $password) {
        $rpc = Gapper::get_RPC();
        return $rpc->gapper->user->verify($token, $password);

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


