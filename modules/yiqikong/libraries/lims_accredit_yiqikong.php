<?php

class LIMS_Accredit_Yiqikong extends LIMS_Accredit {

    public function get_user_info($form = []) {

        $rpc_conf = Config::get('rpc.servers')[$form['source']];
        $url = $rpc_conf['url'];
        $rpc = new RPC($url);
        if (!$rpc->YiQiKong->authorize($rpc_conf['client_id'], $rpc_conf['client_secret'])) {
            URI::redirect('error/401');
        }
        return $rpc->YiQiKong->User->getCurrent($form['token']);
    }

    public function find_user_by_info ($info) {
        if ($gapper_id = $info['gapper_id']) {
            $user = O('user', ['gapper_id' => $gapper_id]);
            return $user;
        }
        return O('user');
    }
}
