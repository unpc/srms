<?php

class Auth_Gini implements Auth_Handler
{
    public function __construct(array $opt)
    {
    }
    //验证令牌/密码
    public function verify($token, $password)
    {
        $rpc_conf = Config::get('rpc.gateway');
        $url = $rpc_conf['url'];
        $rpc = new RPC($url);
        try {
            if (!$rpc->Gateway->authorize($rpc_conf['client_id'], $rpc_conf['client_secret'])) {
                throw new RPC_Exception;
            }
            return $rpc->Gateway->Auth->Verify($token, $password);
        } catch (RPC_Exception $e) {
            return false;
            //rpc出现问题
        }
    }
    //设置令牌
    public function change_token($token, $new_token)
    {
        //安全问题 禁用
        return false;
    }
    //设置密码
    public function change_password($token, $password)
    {
        //安全问题 禁用
        return false;
    }
    //添加令牌/密码对
    public function add($token, $password)
    {
        //安全问题 禁用
        return false;
    }
    //删除令牌/密码对
    public function remove($token)
    {
        //安全问题 禁用
        return false;
    }
}
