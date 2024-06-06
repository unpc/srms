<?php

class API_Platform {
    
    public static $errors = [
        401 => 'Access Denied',
        500 => 'Internal Error'
    ];

    private function _ready() {
        // TODO config-able whitelist
        $whitelist = Config::get('api.security_ip');
        $whitelist[] = '127.0.0.1';
        $whitelist[] = '172.17.42.1';
        $whitelist[] = $_SERVER["SERVER_ADDR"];

        if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist) && false) {
            throw new API_Exception(self::$errors[401], 401);
        }
        return;
    }

    static function get() {
        $platforms = Q('platform');
        $response = [];
        if ($platforms->total_count()) foreach ($platforms as $platform) {
            $data['id'] = $platform->id;
            $data['name'] = $platform->name;
            $data['contact'] = $platform->contact;
            $data['address'] = $platform->address;
            $data['description'] = $platform->description;
            $response[] = $data;
        }
        return $response;
    }
 
}
