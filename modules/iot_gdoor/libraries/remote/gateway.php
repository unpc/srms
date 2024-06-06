<?php

class Remote_Gateway extends Remote_Base
{
    public static $_client;

    public static function init()
    {
        parent::init();
        self::$_client = new \GuzzleHttp\Client([
            'headers' => [
                'X-Gapper-OAuth-Token' => self::$_access_token
            ],
            'base_uri' => Config::get('gateway.server')['url'],
            'timeout' => 5,
            'http_errors' => true,
        ]);
    }

    public static function getUser($gapper_id)
    {
        self::init();
        if (!$gapper_id) {
            return false;
        }
        $data = @json_decode(self::$_client->get(
            'api/v1/user/'.$gapper_id
        )->getBody()->getContents(), true);
        return $data;
    }
}
