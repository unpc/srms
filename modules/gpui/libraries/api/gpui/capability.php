<?php

/**
 * 绩效考核相关
 */
class API_GPUI_Capability extends API_Common
{
    /**
     * 机时利用率排行
     *
     * @param integer $num 返回个数
     * @return array
     */
    function validDurPer($num = 10)
    {
        if (!Module::is_installed('capability')) return [];
        $this->_ready('gpui');

        $config = Config::get('rpc.servers')['capability'];
        try {
            $rpc = new RPC($config['api']);
            $rpc->set_header([
                "CLIENTID: {$config['client_id']}",
                "CLIENTSECRET: {$config['client_secret']}"
            ]);
            $result = $rpc->capability->getValidDurPer($num);
        } catch (RPC_Exception $e) {
            $result = [];
        }
        return $result;
    }
}
