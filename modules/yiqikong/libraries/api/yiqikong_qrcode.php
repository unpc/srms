<?php

class API_YiQiKong_QrCode extends API_Common
{
    public function verify($username, $token)
    {
        $this->_ready();

        $user = O('user', ['token' => $username]);
        if (!$user->id) {
            return false;
        }
        $cache = Cache::factory();
        if ($cache->get("qrcode_{$user->id}") != $token) {
            return false;
        }
        return true;
    }
}
