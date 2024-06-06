<?php

class API_Eq_Mon {
    /**
       该类实现以下 glogon 的 agent_command
       $config['device_computer.agent_command.cam_channels'][] = 'EQ_Mon::command_cam_channels';
       $config['device_computer.agent_command.chat'][] = 'EQ_Mon::command_chat';
       $config['device_computer.agent_command.cam_capture'][] = 'EQ_Mon::command_cam_capture';
    */

    public static $errors = [
        1001 => '请求来源非法!',
        1002 => '找不到到对应的仪器!',
    ];

    private function _ready() {

        $whitelist = Config::get('api.white_list_eq_mon', []);
        $whitelist[] = $_SERVER['SERVER_ADDR'];

        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            return;
        }

        // whitelist 支持ip段配置 形如 192.168.*.*
        foreach ($whitelist as $white) {
            if (strpos($white, '*')) {
                $reg = str_replace('*', '(25[0-5]|2[0-4][0-9]|[0-1]?[0-9]?[0-9])', $white);
                if (preg_match('/^'.$reg.'$/', $_SERVER['REMOTE_ADDR'])) {
                    return;
                }
            }
        }
        throw new API_Exception(self::$errors[1001], 1001);
    }

    private function _get_equipment($device) {

        $equipment = O('equipment', [
                           'control_mode'    => 'computer',
                           'control_address' => $device
                           ]);

        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        return $equipment;

    }

    private function log() {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            $str = vsprintf($format, $args);
            Log::add(strtr('%name %str', [
                        '%name' => '[GLogon API]',
                        '%str' => $str,
            ]), 'devices');
        }
    }

    static function clean_timeout_observers($device) {

        $now = Date::time();
        $modified = FALSE;

        foreach ((array)$device->observers as $uid => $v) {
            if ($now - $v[0] > 5) {
                unset($device->observers[$uid]);
                $modified = TRUE;
            }
        }
        $device->primary_oid = key((array)$device->observers);
        return $modified;
    }

    static function update_observers($device) {
        $observers = [];
        foreach (array_keys((array)$device->observers) as $uid) {
            $user = O('user', $uid);
            if (!$user->id) continue;
            $observers[$user->id] = $user->name;
        }
        // $device->post_command('observers', array('observers'=>$observers));
        return ['observers' => ['observers'=>$observers]];
    }

    function cam_capture($uuid, $data) {

        $this->_ready();

        $equipment = $this->_get_equipment($uuid);
        $device = (object) $equipment->device;


        $ret = [];

        $width = (int) $data['width'];
        $channel = $data['channel'];
        $chat_stream = $data['chat_stream'];
        if (is_numeric($channel)) $channel = (int) $channel;

        // $user = $data['user'];
        $user_token = $data['user']['username'];
        $user = O('user', ['token'=>$user_token]);


        // $agent = $device->agent(0);




        if ($equipment->id) {
            $now = Date::time();
            $key = $equipment->capture_key;
            if (is_string($key) && $key) {
                $equipment->capture_key_mtime = $now;
                $equipment->save();

                $modified = self::clean_timeout_observers($device);

                if (!isset($device->observers[$user->id])) {
                    /*
                    $device->log('%s[%d] 查看仪器 %s[%d] 频道[%d] KEY:%s',
                                 $user->name, $user->id,
                                 $equipment->name, $equipment->id,
                                 $channel, $key);
                    */
                    $this->log('%s[%d] 查看仪器 %s[%d] 频道[%d] KEY:%s',
                                 $user->name, $user->id,
                                 $equipment->name, $equipment->id,
                                 $channel, $key);

                    $modified = TRUE;
                }

                $device->observers[$user->id] = [$now, $channel];

                if ($now - $device->last_cam_capture_mtime > 2) {
                    $device->last_cam_capture_mtime = $now;

                    $odata = $device->observers[$device->primary_oid] ?: reset($device->observers);
                    $params = [
                        'width'=>$width,
                        'channel'=>$odata[1],
                        'key'=>$key,
                        'chat_stream' => $chat_stream,
                        ];

                    $ips = (array) Config::get('equipment.capture_stream_to');
                    $default_name = Config::get('equipment.default_capture_stream_name');
                    $stream_to = count($ips) ? ($equipment->capture_stream_to ?: $ips[$default_name]['address']) : NULL;

                    //1 不存在capture_stream_to 直接用post_to
                    //2 存在capture_stream_to，但是不support_capture_stream，则用post_to
                    if (!$stream_to || !EQ_Mon::support_capture_stream($equipment)) {
                        //如果没有配置stream_to
                        //或者这个仪器不支持stream(没安装gmonitor)
                        //需要使用upload_to

                        //获取默认upload_to
                        $upload_url = $equipment->capture_upload_to;
                        //如果未配置upload_url
                        //使用默认的upload_url
                        if (!$upload_url) {
                            $default_capture_upload_to = Config::get('equipment.default_capture_upload_to');
                            $all_capture_upload_to = Config::get('equipment.capture_upload_to');
                            $upload_url = $all_capture_upload_to[$default_capture_upload_to]['address'];
                            $upload_url = strtr($upload_url, ['%id'=> $equipment->id]);
                        }
                        //如果默认upload没配置，则走URI::url

                        $params['post_to'] = $upload_url ? : $data['url'];
                    }
                    else {
                        $params['stream_to'] = $stream_to.'/'.$key;
                        $params['stream_from'] = $stream_to.'/'.$key.'_chat';
                    }

                    // $device->post_command('cam_capture', $params);
                    $ret['cam_capture'] = $params;

                }

                if ($modified) {
                    $ret += self::update_observers($device);
                }

                // $e->return_value = $device->is_streaming;
            }
            else {
                if ($now - $device->last_cam_capture_mtime > 2) {
                    $device->last_cam_capture_mtime = $now;
                    // $device->post_command('server_time', array('time'=>Date::time()));
                    $ret['server_time'] = ['time'=>Date::time()];

                }
            }
        }

        /** @todo device_agent 机制下, last_cam_capture_mtime,
         * observers 等临时属性是在内存中保存. glogon_server 机制中,
         * 改为了使用数据库保存. 使用数据库开销较大, 此处需要修改, 使
         * 用开销更小的缓存方式, 如 memcached (Xiaopei Li@2014-04-24)
         */
        $equipment->device = $device;
        $equipment->save();

        // return FALSE;

        return $ret;
    }

    function on_chat($device, $struct) {

        $this->_ready();

        $equipment = $this->_get_equipment($device);
        $struct = (object) $struct;

        $text = (string) $struct->text;
        $speaker = $struct->speaker;

        $token = Auth::normalize($speaker['token']);
        $user = O('user', ['token'=>$token]);

        $chat = O('eq_chat');
        $chat->equipment = $equipment;
        $chat->user = $user;
        $chat->name = $speaker['name'];
        $chat->content = $text;
        $chat->save();

        return [];
    }


    function observers($uuid, $struct) {

        $this->_ready();

        $equipment = $this->_get_equipment($uuid);
        $device = (object) $equipment->device;
        $ret = [];

        if (self::clean_timeout_observers($device)) {
            $ret = self::update_observers($device);
        }

        return $ret;
    }

}
