<?php

class Base_Login extends Base_Action
{
    public static function login_success($e, $user)
    {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
        $result = Base_point::getIp();
        $action = O('action');
        // $action->source = $user;
        $action->user = $user;
        $action->ip = $ip;
        // $action->area =
        $action->date = time();
        $action->save();
    }
}
