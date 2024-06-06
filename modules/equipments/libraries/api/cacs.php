<?php

class API_CACS {

    public static $feedback_status_map = [
        1 => EQ_Record_Model::FEEDBACK_NORMAL,
        2 => EQ_Record_Model::FEEDBACK_PROBLEM,
        0 => EQ_Record_Model::FEEDBACK_NOTHING,
    ];

    public static $errors = [
        1001 => '非法 EPC-Server!',
        1002 => '找不到对应的仪器!',
        1003 => '找不到相应的用户!',
        1004 => '用户验证失败!',
        1005 => '用户无权打开仪器!',
        1006 => '用户无权关闭仪器!',
        ];

    private function _log() {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            if ($args) {
                $str = vsprintf($format, $args);
            }
            else {
                $str = $format;
            }
            Log::add("[eq_meter] {$str}", 'devices');
        }
    }

    private function _ready() {

        $whitelist = Config::get('api.white_list_cacs', []);
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

    private function _get_user($user_info) {
        $user = NULL;

        if ($user_info) {

            if ($user_info['card_no']) {
                $card_no = $user_info['card_no'];

                $user = Event::trigger('get_user_from_sec_card', $card_no) ? : O('user', ['card_no' => $card_no]);

                if (!$user->id) {
                    $card_no_s = (string)(($card_no + 0) & 0xffffff);
                    $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ? : O('user', ['card_no_s' => $card_no_s]);
                }
            }
            else if ($user_info['username']) {
                $user = O('user', ['token' => $user_info['username']]);
            }

            if ($user && !$user->id) {
                $user = NULL;
            }
        }

        return $user;
    }

    private function _get_equipment($device, $lang = NULL) {

        $equipment = O('equipment', [
                           'control_address' => $device
                           ]);

        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        return $equipment;
    }

    private function _get_power_on($equipment, $time = NULL) {

        if (!$time) {
            $time = time();
        }

        $power_on = Q("eq_record[dtstart<=$time][dtend=0][equipment=$equipment]")->total_count() == 0;

        return $power_on;
    }

    function connect($control_address, $ipc) {
        $this->_ready();

        $equipment = $this->_get_equipment($control_address);

        // ipc 的使用见 application/libraries/device_agent.php
        $equipment->device2 = ['ipc' => $ipc, 'uuid'=> $control_address];

        $equipment->save();

        $this->_log('%s[%d]已连接', $equipment->name, $equipment->id);

        //写入新的管理卡号
        $cards = [];

        $free_access_cards = $equipment->get_free_access_cards();
        foreach($free_access_cards as $card_no => $user) {
            if (isset($_SERVER['CARD_BYTE_SWAP'])) {
                $card_no = Misc::uint32_to_string(Misc::byte_swap32($card_no));
            }

            $cards[$card_no] = ['name'=>$user->name, 'id'=>$user->id, 'username'=>$user->token];
        }

        $equipment_info = [
            'id' => $equipment->id,
            'name' => $equipment->name,
            'access_cards' => $cards,
            ];

        $now = time();
        $record = Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment]:sort(dtstart D):limit(1)")->current();
        if ($record) {
            $user = $record->user;
            $equipment_info['current_user'] = ['id'=>$user->id, 'name'=>$user->name, 'username' => $user->token];
            $equipment_info['start_time'] = (int) $record->dtstart;
        }

        return $equipment_info;

    }


    function close_pending_switch($control_address, $ps) {
        $this->_ready();
        $equipment = $this->_get_equipment($control_address);

        $this->_log("============= 处理未处理的仪器使用记录 =============");

        $now = time();
        $user = $this->_get_user($ps['user']);

        $p = $ps['power_on'];

        if ($p) {
            // 打开仪器
            // 关闭因意外而打开的记录
            foreach (Q("eq_record[dtend=0][dtstart<=$now][equipment=$equipment]") as $record) {
                if ($record->dtstart==$now) {
                    $record->delete();
                    continue;
                }
                $record->dtend = $now - 1;
                $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                $record->save();
            }

            $record = O('eq_record');
            $record->dtstart = $now;
            $record->dtend = 0;
            $record->user = $user;
            $record->equipment = $equipment;
            $record->save();
        }
        else {
            $record =  Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
            if ($record->id) {
                $record->dtend = $now;

                //负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
                if ($record->user->is_allowed_to('管理使用', $equipment)) {
                    $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                }

                $feedback = $ps['feedback'];

                if ($feedback) {
                    $feedback_status = self::$feedback_status_map;
                    if (isset($feedback_status[($feedback['status'])])) {

                        $feedback['status'] = $feedback_status[($feedback['status'])];
                    }

                    $record->status = $feedback['status'];
                    $record->feedback = $feedback['feedback'];
                    $record->samples = max(Config::get('eq_record.record_default_samples'), (int) $feedback['samples']);

                    if ($feedback['project']) $record->project = $feedback['project'];
                }

                $record->save();
            }

        }

        if ($record) {
            $this->_log("仪器 %s[%d] 用户 %s[%d] 使用记录[%d] %s - %s",
                        $record->equipment->name, $record->equipment->id,
                        $record->user->name, $record->user->id, $record->id,
                        Date::format($record->dtstart), $record->dtend ? Date::format($record->dtend) : '未知');
        }
        else {
            $this->_log("没有PENDING使用记录");
        }
        $this->_log("============= 处理完毕 =============");
    }

    // 接受到 FE1D 被动更新 status
    function be_sync_status($control_address, $status = FALSE) {
        /** @todo RPC 有问题, params 中的 false 无法传来 */

        $this->_ready();
        $equipment = $this->_get_equipment($control_address);

        // $this->_log("%s[%d] 已保持连接", $equipment->name, $equipment->id);

        $equipment->is_monitoring = TRUE;
        $equipment->is_monitoring_mtime = time();

        if ($status != $equipment->is_using) {
            $equipment->is_using = $status;
        }

        return $equipment->save();
    }

    function sync_status($control_address, $status, $last_user = NULL) {

        $this->_ready();
        $equipment = $this->_get_equipment($control_address);

        $last_user = $this->_get_user($last_user);

        $this->_log("---------------同步仪器状态开始---------------");

        /** 更新仪器状态 */
        $equipment->is_using = $status;
        $equipment->is_monitoring = TRUE;
        $equipment->is_monitoring_mtime = time();
        $equipment->save();

        $time = time();

        /** 如果当前打开... */
        if ($status) {

            $record = Q("eq_record[dtstart<=$time][dtend=0][equipment=$equipment]:sort(dtstart D):limit(1)")->current();
            if (!$record->id) {
                $used = Q("{$equipment} user.contact:limit(1)")->current();
                $record = O('eq_record');
                $record->equipment = $equipment;
                $record->user = $last_user ? $last_user : $used;
                $record->dtstart = $time;
                $record->save();
            }
        }
        else {
            // 关闭该仪器所有因意外未关闭的record
            foreach (Q("eq_record[dtend=0][dtstart<$time][equipment=$equipment]") as $record) {
                $record->dtend = $time - 1;
                if ($record->user->is_allowed_to('管理使用', $equipment)) {
                    $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                }
                $record->save();
            }
        }

        $this->_log("%s[%d]状态已同步,当前状态为 : %s", $equipment->name, $equipment->id, $equipment->is_using ? '打开':'关闭');
        $this->_log("---------------同步仪器状态结束---------------");

        return TRUE;
    }



    function get_power_on($control_address) {

        $this->_ready();
        $equipment = $this->_get_equipment($control_address);

        $power_on = $this->_get_power_on($equipment);

        return $power_on;
    }

    function auth($control_address, $card_no) {

        $this->_ready();
        $equipment = $this->_get_equipment($control_address);

        $user = Event::trigger('get_user_from_sec_card', $card_no) ? : O('user', ['card_no' => $card_no]);

        if (!$user->id) {
            $card_no_s = (string)(($card_no + 0) & 0xffffff);
            $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ? : O('user', ['card_no_s' => $card_no_s]);
        }

        if (!$user->id) {
            $this->_log("卡号%012s找不到相应的用户", $card_no);
            throw new API_Exception(self::$errors[1003], 1003);
        }

        Cache::L('ME', $user);  //当前用户切换为该用户

        $time = Date::time();
        $power_on = $this->_get_power_on($equipment, $time);

        $this->_log("卡号:%012s => 用户:%s[%d] %s 尝试操作 仪器 %s[%d]: %s",
                    $card_no, $user->name, $user->id, $user->token,
                    $equipment->name, $equipment->id,
                    $power_on ? '打开' : '关闭'
            );

        if (!$power_on) {

            //要求关闭仪器
            if (!$user->is_allowed_to('管理使用', $equipment)) {
                $record = Q("eq_record[dtstart<=$time][dtend=0][equipment=$equipment][user=$user]:sort(dtstart D):limit(1)")->current();
                if (!$record->id) {
                    // 没使用记录...  检查是否因为没有任何使用记录
                    $this->_log("用户%s[%d]无权关闭%s[%d]", $user->name, $user->id, $equipment->name, $equipment->id);
                    throw new API_Exception(self::$errors[1006], 1006);
                }
            }

        }
        else {
            //要求打开仪器
            //检测用户是否可以操作仪器
            if (!$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, $time)) {
                $this->_log("用户%s[%d]无权打开%s[%d]", $user->name, $user->id, $equipment->name, $equipment->id);
                throw new API_Exception(self::$errors[1005], 1005);
            }
        }

        return ['power_on' => $power_on,
                'user' => ['name'=>$user->name, 'id'=>$user->id, 'username'=>$user->token]];

    }

}
