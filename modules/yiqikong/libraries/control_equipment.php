<?php

use \Pheanstalk\Pheanstalk;

class Control_Equipment
{
    static function on_door_saved($e, $door, $old_data, $new_data)
    {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        $chargeUsers = [];
        $charges = Q("{$door} user.incharge");
        foreach ($charges as $charge) {
            $chargeUsers[$charge->id] = ['name' => $charge->name];
        }

        //地理位置
        $tags = [];
        $tag_root = Tag_Model::root('location');
        foreach (Q("{$door} tag_location") as $tag) {
            $tags[$tag->id] = $tag;
            $tag = $tag->parent;
            while ($tag->id && $tag->id != $tag_root->id) {
                if (array_key_exists($tag->id, $tags)) {
                    unset($tags[$tag->id]);
                }
                $tag = $tag->parent;
            }
        }
        foreach ($tags as $tag) {
            $location = [];
            while ($tag->id != $tag_root->id) {
                array_unshift($location, $tag->name);
                $tag = $tag->parent;
            }
            break;//产品说取第一个
        }
        $location1 = implode(' > ', $location);

        $icon_file = Core::file_exists(PRIVATE_BASE . 'icons/door/128/' . $door->id . '.png', '*');
        if ($icon_file) $icon_url = Config::get('system.base_url') . 'icon/door.' . $door->id . '.128';

        $data = [
            'location1' => $location1,
            'location2' => $door->location2,
            'name' => $door->name,
            'in_addr' => $door->in_addr,
            'out_addr' => $door->out_addr,
            'lock_id' => $door->lock_id,
            'detector_id' => $door->detector_id,
            'is_open' => $door->is_open,
            'server' => $door->server,
            'single_direction' => $door->is_single_direction ?? false,
            'source_name' => LAB_ID,
            'source_id' => $door->id,
            'ctime' => $door->ctime,
            'mtime' => $door->mtime,
            'incharges' => $chargeUsers,
            'icon' => $icon_url ?: '',
        ];

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => "door",
            'body' => $data
        ];
        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));
        return TRUE;

    }

    static function on_door_deleted($e, $door)
    {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'source_name' => LAB_ID,
            'source_id' => $door->id
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => "door/0",
            'body' => $data
        ];
        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_vidcam_saved($e, $vidcam, $old_data, $new_data)
    {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        $chargeUsers = [];
        $charges = Q("{$vidcam} user.incharge");
        foreach ($charges as $charge) {
            $chargeUsers[$charge->id] = ['name' => $charge->name];
        }

        $data = [
            'name' => $vidcam->name,
            'name_abbr' => $vidcam->name_abbr,
            'location' => $vidcam->location,
            'location2' => $vidcam->location2,
            'control_address' => $vidcam->control_address,
            'stream_address' => $vidcam->stream_address,
            'ip_address' => $vidcam->ip_address,
            'is_monitoring' => $vidcam->is_monitoring,
            'is_monitoring_mtime' => $vidcam->is_monitoring_mtime,
            'uuid' => $vidcam->uuid,
            'type' => $vidcam->type,
            'ctime' => $vidcam->ctime,
            'atime' => $vidcam->atime,
            'mtime' => $vidcam->mtime,
            'incharges' => $chargeUsers,
            'source_name' => LAB_ID,
            'source_id' => $vidcam->id,
        ];

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => "vidcam",
            'body' => $data
        ];
        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;

    }

    static function on_vidcam_deleted($e, $vidcam)
    {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'source_name' => LAB_ID,
            'source_id' => $vidcam->id
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => "vidcam/0",
            'body' => $data
        ];
        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));
        return TRUE;
    }

    static function on_vidcam_equipment_connect($e, $equipment, $vidcam, $type)
    {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'equipment' => $equipment->id,
            'vidcam' => $vidcam->id,
            'type' => $type,
        ];

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => 'equipment/vidcam',
            'body' => $data
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_vidcam_equipment_disconnect($e, $equipment, $vidcam, $type)
    {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'equipment' => $equipment->id,
            'vidcam' => $vidcam->id,
            'type' => $type,
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => 'equipment/vidcam',
            'body' => $data
        ];
        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    public static function door_auth($e, $params, $data, $query)
    {
        $user = O('user', $query['user_local']);
        $door = O('door', $query['door']);
        $direction = $query['direction'] == 'out' ? false : true;

        try {

            if (!$user->id || !$door->id) {
                throw new Exception('对象不存在', 404);
            }

            $user_perms = $user->all_perms();
            if (!(in_array('管理所有门禁', $user_perms)
                || in_array('管理所有内容', $user_perms)
                || in_array('远程控制负责的门禁', $user_perms) && $door->id && Q("{$door}<incharge {$user}")->total_count()
            )) {
                throw new Exception('您没有权限进行此操作', 403);
            }

            if (!$direction && $door->is_single_direction) {
                throw new Exception('门禁不支持出门操作', 403);
            }

            $client = new \GuzzleHttp\Client([
                'base_uri' => $door->server,
                'http_errors' => false,
                'timeout' => Config::get('device.gdoor.timeout', 10),
            ]);

            $success = (boolean)$client->post('open', [
                'form_params' => [
                    'uuid' => $door->device['uuid'],
                    'user' => [
                        'username' => $user->token,
                        'name' => $user->name,
                    ],
                ],
            ])->getBody()->getContents();

            Cache::L('ME',$user);
            if ($success && Event::trigger('door.' . ($query['direction'] ?? 'out'), $door)) {
                $e->return_value = ['message' => '操作成功', 'code' => 200];
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    static function get_feedback_schema ($e, $sources, $params) {

        if ($sources != 'feedback_schema') return;
        $query = [];
        $query['equipment_id'] = $params['equipment_id'];
        $query['object_name'] = $params['object_name'] ?? '';
        $query['object_id'] = $params['object_id'] ?? 0;

        $settings = Event::trigger('equipment.api.v1.feedback-schema.GET',[], [], $query) ?? [];
        $settings = $settings['properties'] ?? [];

        if (isset($settings['feedback'])){
            $settings['feedback']['properties']['status']['params'][EQ_Record_Model::FEEDBACK_NORMAL] = "正常";
            $settings['feedback']['properties']['status']['params'][EQ_Record_Model::FEEDBACK_PROBLEM] = "故障";
            $settings['feedback']['properties']['status']['type'] = Extra_Model::TYPE_RADIO;
            $settings['feedback']['properties']['status']['require'] = 1;
            $settings['feedback']['properties']['feedback']['type'] = Extra_Model::TYPE_TEXTAREA;
            $settings['feedback']['properties']['feedback']['require'] = 0;
            if (isset($settings['feedback']['properties']['samples'])){
                $settings['feedback']['properties']['samples']['default_value'] = $settings['feedback']['properties']['samples']['default'];
                $settings['feedback']['properties']['samples']['default'] = 1;
                $settings['feedback']['properties']['samples']['require'] = (int)$settings['feedback']['properties']['samples']['require'];
                $settings['feedback']['properties']['samples']['type'] = Extra_Model::TYPE_NUMBER;
                $settings['feedback']['properties']['samples']['params'] = [
                    "0"=>0,
                    "1"=>9999,
                ];
            }
        }

        $e->return_value = $settings;

        return false;

    }


    static function on_user_door_connect($e, $door, $user, $type){
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'user' => ['id' => $user->id, 'name' => $user->name],
            'door' => ['id' => $door->id, 'name' => $door->name],
        ];

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "user/door",
            'body' => $data
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_user_door_disconnect($e, $door, $user, $type){
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'user' => ['id' => $user->id, 'name' => $user->name],
            'door' => ['id' => $door->id, 'name' => $door->name],
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "user/door",
            'body' => $data
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_user_vidcam_connect($e, $vidcam, $user, $type){
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'user' => ['id' => $user->id, 'name' => $user->name],
            'vidcam' => ['id' => $vidcam->id, 'name' => $vidcam->name],
        ];

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "user/vidcam",
            'body' => $data
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_user_vidcam_disconnect($e, $vidcam, $user, $type){
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'user' => ['id' => $user->id, 'name' => $user->name],
            'vidcam' => ['id' => $vidcam->id, 'name' => $vidcam->name],
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "user/vidcam",
            'body' => $data
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

}