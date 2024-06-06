<?php

class Logout_Controller extends Layout_Controller
{

    public function index()
    {

        $user = L('ME');
        $logoutURL = @$_SESSION['#LOGOUT_REFERER'];
        Auth::logout();
        if ($user->id) {
            Log::add(strtr('[application] %user_name[%user_id]成功登出系统', [
                '%user_name' => $user->name,
                '%user_id'   => $user->id,
            ]), 'logon');

            Log::add(strtr('[application] %user_name[%user_id]成功登出系统', [
                '%user_name' => $user->name,
                '%user_id'   => $user->id,
            ]), 'journal');
        }

        //用户自助退出登录后，清空LOGIN_REFERER
        unset($_SESSION['#LOGIN_REFERER']);

        if (isset($_COOKIE['#LOGOUT_REFERER']) || $logoutURL) {
            $http_referer = $_COOKIE['#LOGOUT_REFERER'] ?: ($logoutURL ?: null);
            unset($_COOKIE['#LOGOUT_REFERER']);
            unset($_SESSION['#LOGOUT_REFERER']);
            if ($http_referer) {
                URI::redirect($http_referer);
            }
        }

        URI::redirect('/');

    }

}
