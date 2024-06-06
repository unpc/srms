<?php

class API_Vidmon {

    public static $errors = [
        1001 => '请求来源非法!',
        1002 => '找不到对应的视频设备!',
        1003 => '找不到上传路径',
    ];

    //进行request验证
    private function _ready() {
        $whitelist = Config::get('api.white_list_vidmon', []);
        $whitelist[] = $_SERVER['SERVER_ADDR'];
        
        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) return FALSE;

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

    //记录Log
    private function log() {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            $str = vsprintf($format, $args);
            Log::add(strtr('%name %str', ['%name' => '[Vidcam API]', '%str' => $str]), 'devices');
        }
    }

    //通过address获取vidcam对象
    private function _get_vidcam($address) {

        $vidcam = O('vidcam', ['control_address'=>trim($address)]);

        if (!$vidcam->id) {
            $this->log('无法找到%s关联视频监控', $address);
            throw new API_Exception(self::$errors[1002], 1002);
        }

        return $vidcam;
    }

    //连接
    public function connect($address, $notify_addr) {

        $this->_ready();

        $this->log('视频监控%s尝试连接', $address);

        $vidcam = $this->_get_vidcam($address);

        $this->log('%s[%d]已连接', $vidcam->name, $vidcam->id);

        $upload_url = strtr(Config::get('vidmon.capture_upload_url'), [
            '%vidcam_id'=>$vidcam->id
        ]);

        if (!$upload_url) {
            throw new API_Exception(self::$errors[1003], 1003);
        }

        //device 存所有数据
        $vidcam->device = [
            'ipc'=> $notify_addr,
        ];

        //device2 存储 zmq 使用数据
        $vidcam->device2 = [
            'ipc'=> $notify_addr,
        ];

        $vidcam->server = $notify_addr;
        // 更新capture_key
        $now = Date::time();
        if (!$vidcam->capture_key || $vidcam->capture_key_mtime + 30 < $now) {
            $vidcam->capture_key = Misc::random_password(12);
            $vidcam->capture_key_mtime = $now;
        }
        $vidcam->save();

        return [
            'name'=> $vidcam->name,
            'id'=> $vidcam->id,
            'file'=> $upload_url,
            'key'=> $vidcam->capture_key,
            'capture_duration'=> Config::get('vidmon.capture_duration'),
        ];
    }

    //keep_alive
    // @deprecated 在新vidmonserver(> 0.5.0)已经用不上
    public function keep_alive($address) {

        $this->_ready();

        $vidcam = $this->_get_vidcam($address);

        if ($vidcam->id) {
            // 刷新is_monitoring_mtime
            $now = Date::time();
            $db = ORM_Model::db('vidcam');
            $db->query('UPDATE `vidcam` SET `is_monitoring`=1, `is_monitoring_mtime`=%d WHERE `id`=%d', $now, $vidcam->id);
            $capture_duration = Config::get('vidmon.capture_duration');
            $upload_duration = Config::get('vidmon.upload_duration');

            if (!$vidcam->capture_key || $vidcam->capture_key_mtime + 30 < $now) {
                $vidcam->capture_key = Misc::random_password(12);
                $vidcam->capture_key_mtime = $now;
                $vidcam->save();
            }

            $alarmed_capture_timeout = Config::get('vidmon.alarmed_capture_timeout');                 
            if ($alarmed_capture_timeout > ($now - $vidcam->last_alarm_time)) {
                $capture_duration = Config::get('vidmon.alarmed_capture_duration');
            }
            else {
                $capture_duration = Config::get('vidmon.capture_duration');
            }
            //如果当前时间距离上次capture时间间隔超过系统配置的capture间隔时间，则进行capture, 同时更新last_capture_time，lasst_caputre_time为虚属性
            if ($now - $vidcam->last_capture_time >= $capture_duration) {
                //设定last_capture_time
                $vidcam->last_capture_time = $now;
                $vidcam->save();
            }
        }

        return [
            'key'=> $vidcam->capture_key,
            'last_upload_time'=> $vidcam->last_upload_time,
        ];
    }

    //报警
    public function alarm($address) {

        $this->_ready();

        $vidcam = $this->_get_vidcam($address);

        $now = Date::time();

        //仅保存报警记录
        $vidcam_alarm = O('vidcam_alarm');
        $vidcam_alarm->vidcam = $vidcam;
        $vidcam_alarm->ctime = $now;
        $vidcam_alarm->save();

        $vidcam->last_alarm_time = $now;
        $vidcam->save();

        $alarmed_capture_duration = Config::get('vidmon.alarmed_capture_duration');
        $alarmed_capture_timeout = Config::get('vidmon.alarmed_capture_timeout');

        $capture_duration_dtstart = $now - $alarmed_capture_duration;

        //设定alarm时间之前的在保存的caputre时间范围内的vidcam_capture_data设定is_alarm 为TRUE
        $db = Database::factory();

        $query = strtr("UPDATE `vidcam_capture_data` SET `is_alarm`='1' WHERE `vidcam_id`='%vidcam_id' AND `ctime`>='%capture_duration_dtstart' AND `ctime`<='%now'", ['%vidcam_id'=>$vidcam->id, '%now'=>$now, '%capture_duration_dtstart'=>$capture_duration_dtstart]);

        $db->query($query);

        $this->log('%s[%d]有报警输入', $vidcam->name, $vidcam->id);

        return [
            'alarmed_capture_duration'=> $alarmed_capture_duration,
            'alarmed_capture_timeout'=> $alarmed_capture_timeout,
        ];
    }

    public function close($address) {

        $this->_ready();

        $vidcam = $this->_get_vidcam($address);

        $this->log('%s[%d]断开连接', $vidcam->name, $vidcam->id);

        return FALSE;
    }
}
