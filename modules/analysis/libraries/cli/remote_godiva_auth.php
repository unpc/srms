<?php
class CLI_Remote_Godiva_Auth
{
    // 注册gapper应用
    public static function register($token)
    {
        $Remote_Godiva_Auth = new Remote_Godiva_Auth();
        $server  = $Remote_Godiva_Auth::getServer();        
        $appConfig  = Config::get('remote_godiva_auth.application');

        if (!$token) {
            die('您似乎没有正确的配置token!');
        }

        if (!$appConfig['client_id'] || !$appConfig['client_secret']) {
            die('您似乎没有正确的配置clientId和clientSecret!');
        }

        $request_url = $server['url'] . "app/{$appConfig['client_id']}";

        $options = [
            'headers' => [
                'X-Gapper-OAuth-Token' => $token
            ],
            'json' => [
                'client_secret' => $appConfig['client_secret'],
                'name' => $appConfig['name'],
                'short_name' => $appConfig['shortName'],
                'description' => '',
                'icon' => '',
                'url' => $appConfig['url'],
                'active' => true,
                'show' => true,
                'type' => 'app',
                'api' => [
                    'logout' => '',
                    'entries' => [],
                    "mobiHome" => [],
                    "mobiEntries" => []
                ],
            ]
        ];

        try {
            $result = $Remote_Godiva_Auth::exec($request_url, 'PUT', $options);
            if ($result['id']) {
                Upgrader::echo_success("Done.");
            } else {
                die('无法更新应用');
            }
        } catch (Exception $e) {
            die("无法更新应用");
        }
    }

}
