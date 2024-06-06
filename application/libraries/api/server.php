<?php

class API_Server {

    /*
     * auth认证入口
     * @params
     *    server          string      客户端名称
     *    signature       string      客户端使用自身私钥加密后的字符串
     *    passcode        string      客户端使用服务器公钥签名后的字符串
     	  scope           string      对应服务器的
     * @return
     *    var             string      将客户端私钥加密后的字符串进行解密后用本地私钥进行加密后的字符串，用于客户端进行验证
     */
	function auth($server, $signature, $passcode) {

        if (!$server || !$signature || !$passcode) {
            return FALSE;
        }

        $SSL = new OpenSSL();

        //发送过来已经使用当前服务器公钥进行加密了的数据
        $encrypted_by_pubkey = @base64_decode($passcode);

        //发送过来用原有服务器的私钥签名了的数据
        $signed_by_privkey = @base64_decode($signature);

        $sources = (array) Config::get('rpc.servers');

        //本地的私钥
        $local_privkey = Config::get('rpc.private_key');

        if (!isset($sources[$server])) {
            return FALSE;
        }

        $client = $sources[$server];
        $client_pubkey = $client['public_key'];

        $decrypted_pubkey_code = $SSL->decrypt($encrypted_by_pubkey, $local_privkey, 'private');
        if (!$SSL->verify($decrypted_pubkey_code, $signed_by_privkey, $client_pubkey)) return FALSE;

        $_SESSION['rpc.server'] = $server;

        Log::add(strtr('%server 进行远程验证，验证成功', [
        			'%server' => $server,
        ]), 'api');

        //返回用自己私钥签名的数据
        return @base64_encode($SSL->sign($decrypted_pubkey_code, $local_privkey));
    }
}
