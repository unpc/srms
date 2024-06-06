<?php
class CLI_Gateway
{
    // 注册gapper应用
    public static function register($token)
    {
        $server  = Config::get('gateway.server');
        $appConfig  = Config::get('gateway.application');

        if (!$token) {
            die('您似乎没有正确的配置token!');
        }

        if (!$appConfig['client_id'] || !$appConfig['client_secret']) {
            die('您似乎没有正确的配置clientId和clientSecret!');
        }

        $request_url = $server['url'] . "app/{$appConfig['client_id']}";

        $entries = [];
        if (Module::is_installed('uno')) {
            $entriesConfig = Uno::get_uno_entries();
        } else {
            $entriesConfig = Config::get('gateway.entries', []);
        }
        $entriesUrl = Config::get('gateway.entries_url');
        foreach ($entriesConfig as $ek => $ev) {
            $entries[$ek] = [
                'title' => $ev['title'],
                'uri' => preg_replace('/^http(s)?:\/\/(.*?)\/(.*)$/', 'uno://$3', $entriesUrl.$ek),
            ];
        }

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
                    'entries' => $entries,
                    "mobiHome" => Config::get('gateway.mobiHome') ? : [],
                    "mobiEntries" => Config::get('gateway.mobiEntries') ? : []
                ],
            ]
        ];

        try {
            $result = Gateway::exec($request_url, 'PUT', $options);
            if ($result['id']) {
                Upgrader::echo_success("Done.");
            } else {
                die('无法更新应用');
            }
        } catch (Exception $e) {
            die("无法更新应用");
        }
    }
    // 推送房间资源类型,就第一次需要推
    public static function push_room_resource_type()
    {
        Room::init();
    }

    // 推送资源列表
    public static function push_room_resource()
    {
        Room::pour();
        // http://demo.gapper.in/gapper/gateway/api/v1/room/1/resources?type=device
        // 验证注册结果
    }

    // 推送房间资源状态
    public static function push_room_resource_status()
    {
        if (!Module::is_installed('room')) {
            echo "module room is required! \n";
            return;
        }
        // foreach (Q("room_resource") as $resource) {
        //     $room_id = $resource->room->id;
        //     $equipment = $resource->resource;

        // testing:
        foreach (Q("equipment[id=1574,1573]") as $equipment) {
            $room_id = 1;
            // tesing end

            if (preg_match('/^gmeter/', $equipment->control_address)) {
                $network_status = $equipment->connect ? I18N::T('equipment', '已联网') : I18N::T('equipment', '未联网');
            } else {
                $network_status = $equipment->is_monitoring ? I18N::T('equipment', '已联网') : I18N::T('equipment', '未联网');
            }
            $eq_status = I18N::T('equipment', EQ_Status_Model::$status[$equipment->status]);

            $params = [
                'room' => $room_id,
                'attrs' => [
                    'eq_status' => $eq_status,
                    'using_status' => $equipment->is_using ? I18N::T('equipment', '使用中') : I18N::T('equipment', '未使用'),
                    'network_status' => $network_status
                ]
            ];
            Mqtt_Client::push(
                "/update/resource/device/{$equipment->id}",
                json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
            );
        }
        // }
    }
}
