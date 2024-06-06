<?php

class Remote_Base
{
    public static $_config;
    public static $_access_token;

    public static function init()
    {
        self::$_config = Config::get('gateway');
        self::getAccessToken();
    }

    /**
     * 获取 access_token
     * @return access_token
     */
    public static function getAccessToken()
    {
        $cache        = Cache::factory();
        self::$_access_token = $cache->get('access_token');
        if (self::$_access_token) {
            return;
        }
        $rest = new REST(self::$_config['server']['url']);
        $auth = $rest->post('/auth/app-token', [
            'client_id' => self::$_config['server']['params']['client_id'],
            'client_secret' => self::$_config['server']['params']['client_secret']
        ]);
        if ($auth['access_token']) {
            $cache->set('access_token', $auth['access_token'], $auth['expires_in'] - 100);
        }
        self::$_access_token = $cache->get('access_token');
    }
}
