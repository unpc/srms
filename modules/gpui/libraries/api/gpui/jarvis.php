<?php
class API_GPUI_Jarvis extends API_Common
{
    public static $errors = [
        1001 => '请求来源地址不合法!',
        1002 => '请求服务器未授权!',
        1003 => '请求参数错误!',
        1004 => '请求资源状态异常!',
        1005 => '找不到对应的资源信息!'
    ];

    public static $feedback_status_map = [
        1 => EQ_Record_Model::FEEDBACK_NORMAL,
        -1 => EQ_Record_Model::FEEDBACK_PROBLEM,
        0 => EQ_Record_Model::FEEDBACK_NOTHING
    ];

    private function _log()
    {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            if ($args) {
                $str = vsprintf($format, $args);
            } else {
                $str = $format;
            }
            Log::add("[jarvis] {$str}", 'devices');
        }
    }

    public function equipmentClient($params = [])
    {
        $this->_ready('gpui');

        if (!is_numeric(H($params['code']))) {
            throw new API_Exception(I18N::T('equipments', '请求参数错误!'), 1003);
        }

        $equipment = O('equipment', ['watcher_code' => H($params['code'])]);
        if (!$equipment->id) {
            throw new API_Exception(I18N::T('equipments', '找不到对应的仪器信息!'), 1003);
        }
        if ($equipment->control_mode && $equipment->control_mode != 'bluetooth') {
            throw new API_Exception(I18N::T('equipments', '仪器现在的控制不支持绑定, 请设置为蓝牙控制!'), 1003);
        }

        if ($params['action'] == 'delete') {
            // $equipment->control_mode = '';
            $equipment->server = '';
            $equipment->connect = false;
            $equipment->save();
            $this->_log('平板, 解绑仪器%s[%d]成功', $equipment->name, $equipment->id);
            return [
                'equipmentId' => $equipment->id,
                'controlAddress' => $equipment->control_address,
            ];
        } elseif ($params['action'] == 'patch') {
            $control_address = trim($params['controlAddress']);
            if ($control_address) {
                $other_equipment = Q("equipment[control_address={$control_address}][id!={$equipment->id}]");
                if ($other_equipment->total_count()) {
                    throw new API_Exception(I18N::T('equipments', '不可重复绑定同一蓝牙插座, 请先解绑: ' . H($other_equipment->current()->name)), 1003);
                }
            }

            $equipment->control_address = $control_address;
            $equipment->bluetooth_serial_address = $control_address;
            $equipment->save();
            $this->_log('平板, 仪器%s[%d]解绑/绑定蓝牙成功, %s => %s', $equipment->name, $equipment->id, $params['old_address'], $equipment->control_address);
            return [
                'equipmentId' => $equipment->id,
                'controlAddress' => $equipment->control_address,
            ];
        } else {
            $equipment->control_mode = 'bluetooth';
            $equipment->server = $params['server'];
            $equipment->connect = true;
            $equipment->save();
            $this->_log('平板, 绑定仪器%s[%d]成功, server: %s', $equipment->name, $equipment->id, $params['server']);
            return [
                'equipmentId' => $equipment->id,
                'controlAddress' => $equipment->control_address,
            ];
        }
    }

    public function loginCard($id, $card_no, $check_only = false)
    {
        $this->_ready('gpui');

        $equipment = O('equipment', $id);
        if (!$equipment->id) {
            throw new API_Exception(I18N::T('equipments', '找不到对应的仪器!'), 1002);
        }

        if (!$card_no) {
            throw new API_Exception(I18N::T('equipments', '找不到相应的用户'), 1003);
        }

        $user = Event::trigger('get_user_from_sec_card', $card_no) ?: O('user', ['card_no' => $card_no]);
        if (!$user->id) {
            $card_no_s = (string)(($card_no + 0) & 0xffffff);
            $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ?: O('user', ['card_no_s' => $card_no_s]);
        }

        if (!$user->id) {
            $this->_log('卡号(gapperId)%s尝试失败', $card_no);
            throw new API_Exception(I18N::T('equipments', '找不到相应的用户'), 1003);
        }
        //end

        Cache::L('ME', $user); //store 'ME' into the cache
        $this->_log('卡号(gapperId):%s => 用户:%s[%d]', $card_no, $user->name, $user->id);

        $this->_log('用户%s[%d]判断是否可开启仪器%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

        if (!in_array($equipment->control_mode, ['bluetooth'])) {
            $this->_log('仪器%s[%d]不支持此方式开机', $equipment->name, $equipment->id);
            throw new API_Exception(I18N::T('equipments', '仪器非蓝牙控制, 开机失败!'), 1004);
        }
        //检测用户是否可以操作仪器
        if (!$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, Date::time())) {
            $this->_log('用户%s[%d]无权打开%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);
            $messages = Lab::messages(Lab::MESSAGE_ERROR);
            if (count($messages)) {
                //清空Lab::$messages,得到正确的错误提示
                Lab::$messages[Lab::MESSAGE_ERROR] = [];
                foreach ($messages as $k => $m) {
                    $messages[$k] = I18N::T('equipments', $m);
                }
                throw new API_Exception(join("\n", $messages), 1005);
            } else {
                throw new API_Exception(I18N::T('equipments', '您无权使用%equipment', ['%equipment' => $equipment->name]), 1005);
            }
        }

        $this->_log('用户%s[%d]可以打开%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

        $ret = [];
        if (!$check_only) {
            $ret = $this->_switch_to(['equipment' => $equipment, 'user' => $user, 'power_on' => true]);
        }

        // ugly hack to add eq_reserv info to device_computer
        if (Module::is_installed('eq_reserv')) {
            $now = Date::time();
            $reserv = Q("eq_reserv[user={$user}][equipment={$equipment}][dtstart<={$now}][dtend>={$now}]")->current();
            if ($reserv->id) {
                $ret['reserv_dtstart'] = $reserv->dtstart;
                $ret['reserv_dtend'] = $reserv->dtend;
            }
        }

        if ($check_only) {
            $current_record = Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment][user=$user]:sort(dtstart D):limit(1)")->current();
            $member_type = in_array($current_record->member_type,[0,1,2,3]) ? '学生' : in_array($current_record->member_type,[10,11,12,13]) ? '教师' : '其他';
        }
        return array_merge([
            'id' => $user->id,
            'token' => $user->token,
            'avatar' => $user->icon_url('128'),
            'name' => $user->name,
            'labs' => join(' ', Q("{$user} lab")->to_assoc('id', 'name')),
            'record_id' => $current_record->id ?: null,
            'record_dtstart' => $current_record->dtstart ?: null,
            'record_dtend' => $current_record->dtend ?: null,
            'member_type' => $member_type
        ], $ret ?: []);
    }

    public function logoutCard($id, $card_no, $check_only = false)
    {
        $this->_ready('gpui');

        $equipment = O('equipment', $id);
        if (!$equipment->id) {
            throw API_Exception('找不到对应的仪器!', 1002);
        }

        if (!$card_no) {
            throw new API_Exception(I18N::T('equipments', '找不到相应的用户'), 1003);
        }

        $user = Event::trigger('get_user_from_sec_card', $card_no) ?: O('user', ['card_no' => $card_no]);
        if (!$user->id) {
            $card_no_s = (string)(($card_no + 0) & 0xffffff);
            $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ?: O('user', ['card_no_s' => $card_no_s]);
        }

        if (!$user->id) {
            $this->_log('卡号(gapperId)%s尝试失败', $card_no);
            throw new API_Exception(I18N::T('equipments', '找不到相应的用户'), 1003);
        }

        $this->_log('卡号(gapperId):%s => 用户:%s[%d]', $card_no, $user->name, $user->id);
        $this->_log('用户%s[%d]判断是否可关闭仪器%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

        $now = Date::time();

        if (!$user->is_allowed_to('管理使用', $equipment)) {
            $record = Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment][user=$user]:sort(dtstart D):limit(1)")->current();
            if (!$record->id) {
                // 没使用记录...  检查是否因为没有任何正在使用的记录
                if (Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment]")->total_count() > 0) {
                    $this->_log('用户%s[%d]无权关闭%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);
                    throw new API_Exception(I18N::T('equipments', '您无权关闭%equipment', ['%equipment' => $equipment->name]), 1005);
                }
            }
        }

        if (!$check_only) {
            $ret = $this->_switch_to([
                'user' => $user,
                'power_on' => false,
                'equipment' => $equipment,
            ]);
        }

        $this->_log('用户%s[%d]可以关闭%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

        return $ret;
    }

    public function submitFeedback($record_id, $feedback)
    {
        $this->_ready('gpui');

        $record = O('eq_record', $record_id);
        if (!$record->id) {
            throw new API_Exception(I18N::T('equipments', '找不到相应的使用记录'), 1005);
        }
        if ($feedback) {
            $record->status = $feedback['status'];
            $record->feedback = $feedback['content'];
            if ($feedback['samples']) {
                $record->samples = max(1, (int)$feedback['samples']);
            }
            if ($feedback['project']) {
                $record->project = O('lab_project', $feedback['project']);
            }
            $this->_log(
                '平板, 使用记录[%d]反馈成功, content: %s',
                $record->id,
                json_encode($feedback, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }

        return $record->save();
    }

    private function _switch_to($data)
    {
        $user = $data['user'];
        $power_on = $data['power_on'];
        $equipment = $data['equipment'];

        $now = Date::time();

        $this->_log('%s[%d] 尝试切换%s[%d] (%s) 的状态 => %s', $user->name, $user->id, $equipment->name, $equipment->id, $equipment->location . $equipment->location2, $power_on ? '打开' : '关闭');

        $equipment->is_using = $power_on;
        $equipment->user_using = $power_on ? $user : null;

        $equipment->save();
        if ($power_on) {
            // 关闭该仪器所有因意外未关闭的record
            foreach (Q("eq_record[dtend=0][dtstart<=$now][equipment=$equipment]") as $record) {
                if ($record->dtstart == $now) {
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
        } else {
            $record =  Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
            if ($record->id) {
                $record->dtend = $now;

                //负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
                if ($record->user->is_allowed_to('管理使用', $equipment)) {
                    $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                }
                $record->save();
            }
        }

        $data = [
            'record_id' => $record->id,
            'user_token' => $record->user->name,
            'user_name' => $record->user->name,
            'dtstart' => $record->dtstart,
            'dtend' => $record->dtend ?: 0,
        ];
        return $data;
    }

    public function validate($params = [])
    {
        $this->_ready('gpui');
        if (!isset($params['equipments'])) {
            throw new API_Exception(self::$errors[1003], 1003);
        }

        foreach ($params['equipments'] as $eq_param) {
            $equipment = O('equipment', ['watcher_code' => H($eq_param['code'])]);
            if (!$equipment->id) {
                throw new API_Exception(self::$errors[1005], 1005);
            }
            $equipment->control_mode = 'bluetooth';
            $equipment->server = $params['server'];
            $equipment->control_address = $eq_param['macAddress'];
            $equipment->connect = 1;
            $equipment->save();
        }

        return true;
    }

    public function getFreeAccessCards($params = [])
    {
        $this->_ready('gpui');
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1003], 1003);
        }
        $cards = [];
        $free_access_users = $equipment->get_free_access_users();

        foreach ($free_access_users as $user) {
            if ($user->card_no) {
                // list($token, $backend) = Auth::parse_token($user->token);
                $cards[(string)$user->card_no] = [
                    'id' => $user->id,
                    'token' => $user->token,
                    'name' => H($user->name),
                    'labs' => join(' ', Q("{$user} lab")->to_assoc('id', 'name')),
                ];
            }
        }

        $this->_log('[获取离线预约卡号] 多媒体 在 %s抓取了仪器 %s[%d]特权卡号信息', Date::format(), $equipment->name, $equipment->id);

        return $cards;
    }

    public function getFreeAccessUsers($params = [])
    {
        $this->_ready('gpui');
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1003], 1003);
        }
        $users = [];
        $free_access_users = $equipment->get_free_access_users();

        foreach ($free_access_users as $user) {
            $users[] = [
                'id' => $user->id,
                'gapper_id' => $user->gapper_id,
                'token' => $user->token,
                'name' => H($user->name),
                'labs' => join(' ', Q("{$user} lab")->to_assoc('id', 'name')),
                'card_no' => $user->card_no,
                'ref_no' => $user->ref_no,
            ];
        }

        $this->_log('[获取离线预约卡号] 多媒体 在 %s抓取了仪器 %s[%d]特权用户信息', Date::format(), $equipment->name, $equipment->id);

        return $users;
    }

    public function getOfflineReservCards($params = [])
    {
        $this->_ready('gpui');
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1003], 1003);
        }

        $ret = [];
        $ret['users'] = [];
        $ret['cards'] = [];
        $ret['all'] = [];
        $dtstart = Date::get_day_start();
        $dtend = Date::next_time($dtstart, Config::get('equipment.offline_reserv_day', 5));

        if ($equipment->accept_reserv) {
            $reservs = Q("eq_reserv[equipment={$equipment}][dtstart=$dtstart~$dtend]:sort(dtstart A)");
            // 仪器如果设置了《允许在他人预约时间段使用》的设置的话，那么所有人的均能在预约时段使用
            if ($equipment->unbind_reserv_time) {
                foreach ($reservs as $r) {
                    $ret['all'][] = ['dtstart' => $r->dtstart, 'dtend' => $r->dtend];
                }
            } else {
                foreach ($reservs as $r) {
                    $user = $r->user;
                    $card_no = $user->card_no;
                    if ($card_no) {
                        !is_array($ret['cards'][$card_no]) and $ret['cards'][$card_no] = [];
                        $ret['cards'][$card_no][] = ['dtstart' => $r->dtstart, 'dtend' => $r->dtend];
                        // list($token, $backend) = Auth::parse_token($user->token);
                        $ret['users'][$card_no] = [
                            'id' => $user->id,
                            'token' => $user->token,
                            'name' => H($user->name),
                            'labs' => join(' ', Q("{$user} lab")->to_assoc('id', 'name')),
                            'reserv_dtstart' => $r->dtstart,
                            'reserv_dtend' => $r->dtend
                        ];
                    }
                    $gapper_id = $user->gapper_id;
                    if ($gapper_id) {
                        $ret['users'][$gapper_id] = [
                            'id' => $user->id,
                            'token' => $user->token,
                            'name' => H($user->name),
                            'labs' => join(' ', Q("{$user} lab")->to_assoc('id', 'name')),
                            'reserv_dtstart' => $r->dtstart,
                            'reserv_dtend' => $r->dtend
                        ];
                    }
                }
            }
        } elseif ($equipment->require_training) {
            $trains = O('ue_training', ['equipment' => $equipment, 'status' => UE_Training_Model::STATUS_APPROVED]);
            foreach ($trains as $train) {
                // 如果不过期或者过期时间在当天开始时间之后的话均需要进行卡号存储
                if ($train->atime == 0 || $train->atime > $dtstart) {
                    $user = $train->user;
                    $card_no = $user->card_no;
                    if ($card_no) {
                        !is_array($ret['cards'][$card_no]) and $ret['cards'][$card_no] = [];
                        // 如果不过期的话则取标准结束时间, 否则需要根据到期时间与结束时间的对比来进行选择最小值
                        $end = $train->atime == 0 ? $dtend : min($dtend, $train->atime);
                        $ret['cards'][$card_no][] = ['dtstart' => $dtstart, 'dtend' => $end];
                        // list($token, $backend) = Auth::parse_token($user->token);
                        $ret['users'][$card_no] = [
                            'name' => H($user->name),
                            'token' => $user->token,
                        ];
                    }
                }
            }
        } else {
            // 没有预约且没有需要培训才能使用的限制情况下，所有人均可以进行随意使用
            $ret['all'][] = ['dtstart' => $dtstart, 'dtend' => $dtend];
        }

        $this->_log(
            '[获取离线预约卡号] jarvis 在 %s抓取了仪器 %s[%d]离线预约卡号信息(%s ~ %s): %s',
            Date::format(),
            $equipment->name,
            $equipment->id,
            Date::format($dtstart),
            Date::format($dtend),
            @json_encode($ret, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $ret;
    }

    public function getOfflineReservUsers($params = [])
    {
        $this->_ready('gpui');
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1003], 1003);
        }

        $ret = [];
        $ret['users'] = [];
        $ret['all'] = [];
        $dtstart = Date::get_day_start();
        $dtend = Date::next_time($dtstart, Config::get('equipment.offline_reserv_day', 5));

        if ($equipment->accept_reserv) {
            $reservs = Q("eq_reserv[equipment={$equipment}][dtstart=$dtstart~$dtend]:sort(dtstart A)");
            // 仪器如果设置了《允许在他人预约时间段使用》的设置的话，那么所有人的均能在预约时段使用
            if ($equipment->unbind_reserv_time) {
                foreach ($reservs as $r) {
                    $ret['all'][] = ['dtstart' => $r->dtstart, 'dtend' => $r->dtend];
                }
            } else {
                foreach ($reservs as $r) {
                    $user = $r->user;
                    $ret['users'][] = [
                        'id' => $user->id,
                        'token' => $user->token,
                        'name' => H($user->name),
                        'labs' => join(' ', Q("{$user} lab")->to_assoc('id', 'name')),
                        'dtstart' => $r->dtstart,
                        'dtend' => $r->dtend,
                        'card_no' => $user->card_no,
                        'gapper_id' => $user->gapper_id,
                        'ref_no' => $user->ref_no,
                    ];

                }
            }
        } elseif ($equipment->require_training) {
            $trains = O('ue_training', ['equipment' => $equipment, 'status' => UE_Training_Model::STATUS_APPROVED]);
            foreach ($trains as $train) {
                // 如果不过期或者过期时间在当天开始时间之后的话均需要进行卡号存储
                if ($train->atime == 0 || $train->atime > $dtstart) {
                    $user = $train->user;
                    // 如果不过期的话则取标准结束时间, 否则需要根据到期时间与结束时间的对比来进行选择最小值
                    $end = $train->atime == 0 ? $dtend : min($dtend, $train->atime);
                    $ret['users'][] = [
                        'name' => H($user->name),
                        'token' => $user->token,
                        'labs' => join(' ', Q("{$user} lab")->to_assoc('id', 'name')),
                        'dtstart' => $dtstart,
                        'dtend' => $end,
                        'id' => $user->id,
                        'card_no' => $user->card_no,
                        'gapper_id' => $user->gapper_id,
                        'ref_no' => $user->ref_no,
                    ];
                }
            }
        } else {
            // 没有预约且没有需要培训才能使用的限制情况下，所有人均可以进行随意使用
            $ret['all'][] = ['dtstart' => $dtstart, 'dtend' => $dtend];
        }

        $this->_log(
            '[获取离线预约卡号] jarvis 在 %s抓取了仪器 %s[%d]离线预约卡号信息(%s ~ %s): %s',
            Date::format(),
            $equipment->name,
            $equipment->id,
            Date::format($dtstart),
            Date::format($dtend),
            @json_encode($ret, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $ret;
    }

    public function submitOfflineRecords($params = [])
    {
        $this->_ready('gpui');
        $equipment = $this->_getEquipment($params['code']);
        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1003], 1003);
        }

        $offline_record = $params['record'];
        $ret = [];

        if (count($offline_record)) {
            // 第三代大平板Glogon/Gmeter + Monitor 模式下可能存在需要增加用户名密码输入的可能性；
            // 所以使用兼容我们
            $card_no = (string) $offline_record['card'];
            if ($card_no) {
                $user = Event::trigger('get_user_from_sec_card', $card_no) ?: O('user', ['card_no' => $card_no]);
                if (!$user->id) {
                    $card_no_s = (string)(($card_no + 0) & 0xffffff);
                    $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ?: O('user', ['card_no_s' => $card_no_s]);
                }
                if (!$user->id) {
                    $this->_log('[更新离线记录] 卡号 %s 找不到相应的用户', $card_no);
                    $user = O('user');
                } else {
                    $this->_log('[更新离线记录] 卡号:%s => 用户:%s[%d]', $card_no, $user->name, $user->id);
                }
            } else if(isset($offline_record['token']) && $offline_record['token']) {
                $token = Auth::normalize($offline_record['token']);
                $user = O('user', ['token' => $token]);
                if (!$user->id) {
                    $this->_log('[更新离线记录] 登录名%s找不到相应的用户', $token);
                }
            } else if(isset($offline_record['user'])) {
                if (isset($offline_record['user']['id'])) {
                    $user = O('user', $offline_record['user']['id']);
                    if (!$user->id) {
                        $this->_log('[更新离线记录] 用户ID %s找不到相应的用户', $offline_record['user']['id']);
                    }
                } else {
                    $this->_log('[更新离线记录] 找不到相应的用户,没有可识别的身份特征');
                }
            } else {
                $this->_log('[更新离线记录] 找不到相应的用户,没有可识别的身份特征');
            }

            $time = (int) $offline_record['time'];
            switch ($offline_record['status']) {
                case 'login':
                    // 清理目前仪器所有因意外未关闭的使用记录
                    foreach (Q("eq_record[dtend=0][dtstart<$time][equipment=$equipment]") as $record) {
                        if ($record->dtstart == $time) {
                            $record->delete();
                            continue;
                        }

                        $record->dtend = $time - 1;
                        $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                        $record->save();
                    }

                    $record = O('eq_record', ['equipment' => $equipment, 'dtstart' => $time]);
                    if (!$record->id) {
                        $record->dtstart = $time;
                        $record->equipment = $equipment;
                    }
                    $record->is_computer_device = true;
                    $record->dtend = 0;
                    $record->user = $user;

                    if ($record->save()) {
                        Event::trigger('equipments.glogon.offline.login.record_saved', $record);
                    }
                    $equipment->is_using = true;
                    $equipment->save();

                    //刷新对象
                    if ($user->id) {
                        $this->_log('[更新离线记录] %s[%d] 在 %s 登入仪器 %s[%d]', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id);
                    } else {
                        $this->_log('[更新离线记录] 未知卡号 %s 在 %s 登入仪器 %s[%d]', $card_no ?: '--', Date::format($time), $equipment->name, $equipment->id);
                    }
                    break;
                case 'logout':
                    if (!$user->id) {
                        //open last open record
                        $record = Q("eq_record[dtstart<=$time][dtend=0][equipment=$equipment]:sort(dtstart D):limit(1)")->current();
                        if ($record->id) {
                            $user = $record->user;
                        }
                    }
                    if ($user->id) {
                        $record =  Q("eq_record[dtstart<$time][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
                        if ($record->id) {
                            $this->_log('[更新离线记录] %s[%d] 在 %s 登出仪器 %s[%d]', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id);
                            $now = Date::time();
                            $record->dtend = min($time, $now);
                            if ($offline_record['extra']) {
                                $feedback = @json_decode($offline_record['extra'], true);
                            } else {
                                $feedback = @json_decode($offline_record['feedback'], true);
                            }
                            if (is_array($feedback)) {
                                $feedback_status = self::$feedback_status_map;

                                if (isset($feedback_status[$feedback['status']])) {
                                    $record->status = (int)$feedback['status'];
                                }
                                $record->feedback = $feedback['content'];
                                if ($record->dtend == $now) {
                                    $record->feedback .= "\n客户端时间异常, 已自动矫正结束时间";
                                }
                                // 如果仪器故障，可以填0
                                if ($record->status !== EQ_Record_Model::FEEDBACK_PROBLEM) {
                                    $record->samples = max(Config::get('eq_record.record_default_samples', 1), (int)$feedback['samples']);
                                } else {
                                    $record->samples = (int)$feedback['samples'];
                                }
                                if ($feedback['project']) {
                                    $record->project = O('lab_project', $feedback['project']);
                                }
                            }

                            //负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
                            if ($record->status == EQ_Record_Model::FEEDBACK_NOTHING && $record->user->is_allowed_to('管理使用', $equipment)) {
                                $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                            }

                            if ($record->save()) {
                                Event::trigger('equipments.glogon.offline.logout.record_saved', $record, null);
                            }
                        } else {
                            $this->_log('[更新离线记录] %s[%d] 在 %s 登出仪器 %s[%d] 但没找到相应记录', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id);
                        }
                    } else {
                        //离线使用后，恢复网络，关闭使用记录
                        if ($record->id) {
                            if ($offline_record['extra']) {
                                $extra = @json_decode($offline_record['extra'], true);
                                $status = $extra['status'];
                                $record->samples = $extra['samples'];
                                $record->feedback = $extra['content'];
                            } else {
                                $status = $offline_record['status'];
                            }

                            $record->status = $status;
                            $record->dtend = min($time, $now);
                            if ($record->save()) {
                                Event::trigger('equipments.glogon.offline.logout.record_saved', $record, null);
                            }
                        }
                        $this->_log('[更新离线记录] 未知卡号 %s 在 %s 登出仪器 %s[%d]', $card_no ?: '--', Date::format($time), $equipment->name, $equipment->id);
                    }
                    $equipment->is_using = false;
                    $equipment->save();
                    break;
                case 'error':
                    $this->_log('[更新离线记录] %s[%d] %s 在 %s 尝试 %s 登录失败', $user->name, $user->id, $card_no ?: '--', Date::format($time), $card_no ? '刷卡' : '移动登陆');
                    break;
            }
            $ret['confirmed'] = true;
        }
        return $ret;
    }


    private function _getEquipment($uuid = '')
    {
        if (!$uuid) {
            return false;
        }
        $equipment = O('equipment', ['watcher_code' => $uuid]);

        // $equipment = O('equipment', ['control_mode' => 'computer', 'control_address' => $uuid]);
        // if (!$equipment->id) {
        //     $equipment = O('equipment', ['control_mode' => 'power', 'control_address' => 'gmeter://'.$uuid]);
        // }
        // if (!$equipment->id) {
        //     $equipment = O('equipment', $uuid);
        // }
        // if (!$equipment->id) {
        //     $equipment = O('equipment', ['yiqikong_id' => $uuid]);
        // }

        return $equipment;
    }
}
