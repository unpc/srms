<?php

class Remote_Godiva_Auth
{
    public static function getServer() {
        if (Config::get('remote_godiva_auth.server')) {
            return Config::get('remote_godiva_auth.server');
        }
        return Config::get('gateway')['server'];
    }

    public static function exec($url, $method = 'GET', $options = [])
    {
        try {
            $client   = new \GuzzleHttp\Client(['timeout' => 30, 'verify' => false]);
            $response = $client->request($method, $url, $options);
            if ($response->getStatusCode() == 200) {
                $response = json_decode($response->getBody()->getContents(), true);
                return $response;
            }
        } catch (Exception $e) {
        }
        return false;
    }

    public static function refreshToken($refreshToken)
    {   
        $server = self::getServer();
        $options = [
            'json' => [
                'client_id'     => $server['params']['client_id'],
                'client_secret' => $server['params']['client_secret'],
                'refresh_token' => $refreshToken,
            ],
        ];
        $request_url = $server['url'] . 'auth/refresh-token';
        $response = static::exec($request_url, 'POST', $options);
        if (!$response['access_token']) {
            $cache = Cache::factory();
            $cache->remove('remote_refresh_token');
            if (PHP_SAPI != 'cli') {
                Auth::logout();
                URI::redirect('/');
            }
            return null;
        }

        return $response;
    }

    public static function getToken()
    {
        $cache        = Cache::factory();
        $access_token = $cache->get('remote_access_token');

        if ($access_token) {
            return $access_token;
        }

        $refreshToken = $cache->get('remote_refresh_token');
        if ($refreshToken) {
            $data = self::refreshToken($refreshToken);
            if ($data['access_token'] && $data['expires_in']) {
                $cache->set('remote_access_token', $data['access_token'], $data['expires_in'] - 100);
                return $data['access_token'];
            }
        }

        $server  = self::getServer();
        $options = [
            'json' => $server['params'],
        ];
        $request_url = $server['url'] . 'auth/app-token';
        $auth        = static::exec($request_url, 'POST', $options);
        $cache->set('remote_access_token', $auth['access_token'], $auth['expires_in'] - 100);
        // refresh_token默认有效期为7天
        $cache->set('remote_refresh_token', $auth['refresh_token'], 86400 * 7 - 100);
        return $auth['access_token'];
    }
}
