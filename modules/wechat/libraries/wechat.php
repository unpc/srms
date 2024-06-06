<?php

class Wechat {

    // 未绑定
    const BIND_STATUS_NOT_YET = 0;
    // 已绑定
    const BIND_STATUS_SUCCESS = 1;

    //本地绑定
    static function user_call_bind($e, $user, $params) {

        $openid = $params[0]['openid'];
        $gapper_id = $params[0]['user'];

        $user
            ->set('wechat_bind_status', self::BIND_STATUS_SUCCESS)
            ->set('wechat_openid', $openid)
            ->set('gapper_id', $gapper_id)
            ->save();
    }

    //本地解绑
    static function user_call_unbind($e, $user, $sync_remote = FALSE) {

        //先保存下来
        $openid = $user->wechat_openid;

        $user
            ->set('wechat_bind_status', self::BIND_STATUS_NOT_YET)
            ->set('wechat_openid', NULL)
            ->save();

        //如果远程也需要 unbind
        if ($sync_remote) {

            //$rpc = new RPC('http://gapper.in/api');

            //$client_id = Config::get('gapper.client_id');
            //$client_secret = Config::get('gapper.client_secret');

            //auth
            //$rpc->Gapper->Authorize($client_id, $client_secret);
            //$result = $rpc->Gapper->User->UnlinkIdentity((int) $user->gapper_id, 'wechat', $openid);

            // 解绑 yiqikong-user
            $rpc = new RPC(Config::get('yiqikong_user.url'));
            $result = $rpc->YiQiKong->User->unbind((int) $user->gapper_id);

            if ($result) {

                //绑定成功后, 同步告诉所有其他站点, 进行解绑
                Debade_Queue::of('Lims-CF')->push(
                [
                    'method'=> 'wechat/unbind',
                    'params'=> [
                        'user'=> $user->gapper_id,
                    ],
                ]
                , 'Lims-CF');
                /*Debade_Queue::of('YiQiKong')->push(
                [
                    'method'=> 'wechat/unbind',
                    'params'=> [
                        'gapper_id'=> $user->gapper_id,
                    ],
                ]
                , 'wechat');*/
            }
        }
    }

    // 用于扩展 user_links
    static function user_links($e, $user, $links, $mode) {

        $me = L('ME');

        switch($mode) {
            case 'view' :
            if ($me->id == $user->id) {
                $links['wechat'] = [
                    'html'=> (string) V('wechat:links/user', ['user'=> $user]),
                ];
            }
        }
    }

    static function equipment_links($e, $equipment, $links, $mode) {

        switch($mode) {
            case 'wechat' :
            $links['wechat'] = [
                'html'=> (string) V('wechat:links/equipment', ['equipment'=> $equipment]),
            ];
        }
    }

    // 用于扩展 equipemtn_extra_view
    static function equipment_qrcode($e, $equipment) {

        $e->return_value = (string)V('wechat:qrcode/extra_equipment', ['equipment' => $equipment]);

        return FALSE;
    }
}
