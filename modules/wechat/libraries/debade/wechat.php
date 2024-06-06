<?php

class Debade_Wechat {

    // params 列表:
    //  user
    public static function action_bind($params) {
        $user = O('user', ['email' =>$params['email']]);

        if ($user->id) {
            $user->wechat_bind($params);
        }
    }

    // params 列表:
    //   user
    public static function action_unbind($params) {
        $user = O('user', ['gapper_id'=> $params['user']]);

        if ($user->id) {
            $user->wechat_unbind();
        }
    }
}
