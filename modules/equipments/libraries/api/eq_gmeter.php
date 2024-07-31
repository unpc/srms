<?php

/**
   应用级别错误代码:
   1001: 非法 EPC-Server!
   1002: 找不到对应的仪器!
   1003: 找不到相应的用户!
   1004: 用户验证失败!
   1005: 用户无权打开仪器!
**/
class API_EQ_GMeter {

    private function _ready() {
        // $this->_log('epc-server %s trying to connect', $_SERVER['REMOTE_ADDR']);

        // TODO config-able whitelist
        $epc_whitelist = Config::get('api.white_list_epc', []);
        $epc_whitelist[] = $_SERVER['SERVER_ADDR'];

        if (in_array($_SERVER['REMOTE_ADDR'], $epc_whitelist)) {
            return;
        }

        // whitelist 支持ip段配置 形如 192.168.*.*
        foreach ($epc_whitelist as $white) {
            if (strpos($white, '*')) {
                $reg = str_replace('*', '(25[0-5]|2[0-4][0-9]|[0-1]?[0-9]?[0-9])', $white);
                if (preg_match('/^'.$reg.'$/', $_SERVER['REMOTE_ADDR'])) {
                    return;
                }
            }
        }
        throw new API_Exception('access denied', 1001);
    }

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

    function connect($uuid, $rest_api) {
        $this->_ready();
        $this->_log('epc-device connect, uuid: ' . $uuid);

        $control_address = 'Biot://'.$uuid;
        $equipment = O('equipment', ['control_address'=>$control_address]);
        if (!$equipment->id) throw new API_Exception('找不到对应的仪器!', 1002);

        // ipc 的使用见 application/libraries/device_agent.php
        $equipment->device2 = ['uuid'=> $uuid];
        $equipment->server = $rest_api;
        $equipment->connect = TRUE;

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
            'save_meter_data' => Module::is_installed('eq_meter')
        ];

        $now = time();
        $record = Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment]:sort(dtstart D):limit(1)")->current();
        if ($record) {
            $user = $record->user;
            $equipment_info['current_user'] = ['id'=>$user->id, 'name'=>$user->name, 'username' => $user->token];
            $equipment_info['start_time'] = (int) $record->dtstart;
        }

        $equipment_info = new ArrayIterator($equipment_info);
        Event::trigger('api.eq_gmeter.connect.extra.keys', $equipment_info, $equipment);

        return (array)$equipment_info;

    }

    function save_data($uuid, $data) {
        $now = Date::time();
        $eq_meter = O('eq_meter', ['uuid' => $uuid ]);
        $equipment = $eq_meter->equipment;

        if ($eq_meter->id && $equipment->id) {
            foreach ($data as $dt) {
                $eq_meter_data = json_decode($dt, TRUE);

                $timestamp = $eq_meter_data['timestamp'];
                if (!$timestamp) continue;

                $amp = (float)$eq_meter_data['g_meter_amp'];
                $watt = (float)$eq_meter_data['g_meter_watt'];
                $voltage = (float)$eq_meter_data['g_meter_voltage'];

                $eq_meter_data = O('eq_meter_data');
                $eq_meter_data->eq_meter = $eq_meter;
                $eq_meter_data->ctime = $timestamp;
                $eq_meter_data->amp = $amp;
                $eq_meter_data->watt = $watt;
                $eq_meter_data->voltage = $voltage;

                if ($eq_meter_data->save()) {
                    if (Config::get('debug.eq_meter', FALSE)) {
                        $this->_log('仪器%s[%d]的电流数据%s、电压数据%s、功率数据%s、数据发送的时间戳%s',
                            $equipment->name, $equipment->id, $amp, $voltage, $watt, $timestamp);
                    }
                    /*
                        如果设置了仪器eq_meter的阈值，则进行记录判断,
                        字段先定义为is_set_eq_meter_threshold
                        最大watt阈值和最小watt阈值先定义为 watt_threshold_max，watt_threshold_min
                    */
                    //如果都没有设置，则threshold_min 设置为40 标示默认为 40 - NULL
                    //需要考虑用户本来就设置threshold_min为null的情况，所以需要同时要watt_threshold_max为NULL才可以
                    $threshold_min = $eq_meter->watt_threshold_min;
                    if (!$eq_meter->watt_threshold_max && !$eq_meter->watt_threshold_min) {
                        $threshold_min = Config::get('eq_meter.watt_threshold_min', 40);
                    }

                    $threshold_max = $eq_meter->watt_threshold_max;
                    if (
                        /*
                        * 如果不存在最大值 并且watt大于最小值
                        * 如果同时存在最大值和最小值  并且watt在两者之间
                        * 如果不存在最小值 兵千watt小于最大值
                        */
                        (!$threshold_max && $threshold_min && ($watt >= $threshold_min))||
                        ($threshold_min && $threshold_max && $watt >= $threshold_min && $watt <= $threshold_max) ||
                        (!$threshold_min && $threshold_max && $watt <= $threshold_max)
                        ) {
                        if (Q("eq_meter_record[eq_meter={$eq_meter}][dtstart<$now][dtend=0]")->total_count() == 0) {
                            /*
                                如果有未闭合的记录，则不作处理
                                没有则生成一条新的数据
                            */
                            $eq_meter_record = O('eq_meter_record');
                            $eq_meter_record->eq_meter = $eq_meter;
                            $eq_meter_record->dtstart = $timestamp;
                            $eq_meter_record->dtend = 0;
                            $ret = $eq_meter_record->save();
                            if (Config::get('debug.eq_meter', FALSE)) {
                                if ($ret) {
                                    $this->_log('仪器%s[%d]的eq_meter记录保存成功并启动,开始时间为: %s, 对应的data数据记录id为 %d',
                                        $equipment->name, $equipment->id, $timestamp, $eq_meter_data->id);
                                }
                                else {
                                    $this->_log('仪器%s[%d]的eq_meter记录保存失败, 对应的data数据记录id为 %d',
                                        $equipment->name, $equipment->id, $eq_meter_data->id);
                                }
                            }
                        }
                    }
                    else {
                        /*
                            数据异常的时候之前有一条未闭合的记录, 则闭合这条记录，没有则不作处理
                            没有未闭合的数据则不作处理
                        */
                        $eq_meter_record = Q("eq_meter_record[eq_meter={$eq_meter}][dtstart<$now][dtend=0]:limit(1)")->current();

                        if ($eq_meter_record->id) {
                            $eq_meter_record->dtend =$timestamp;
                            $ret = $eq_meter_record->save();
                            if (Config::get('debug.eq_meter', FALSE)) {
                                if ($ret) {
                                    $this->_log('仪器%s[%d]的eq_meter记录保存成功且闭合,开始时间为: %s, 结束时间为: %s,对应的data数据记录id为 %d',
                                        $equipment->name, $equipment->id, $eq_meter_record->dtstart, $timestamp, $eq_meter_data->id);
                                }
                                else {
                                    $this->_log('仪器%s[%d]的eq_meter记录保存失败, 对应的data数据记录id为 %d',
                                        $equipment->name, $equipment->id, $eq_meter_data->id);
                                }
                            }
                        }
                    }
                }
            }
            return ['result'=>1];
        }
        else {
            return ['result'=>0];
        }

    }

    function disconnect($id) {
        $this->_ready();
        $db = ORM_Model::db('equipment');
        $db->query('UPDATE `equipment` SET `is_monitoring`=0, `is_monitoring_mtime`=0 WHERE `id`=%d', $id);
    }

    private function _switch_to($data) {

        $user = $data['user'];
        $power_on = $data['power_on'];
        $equipment = $data['equipment'];

        $now = Date::time();

        $this->_log('%s[%d] 尝试切换%s[%d] (%s) 的状态 => %s', $user->name, $user->id, $equipment->name, $equipment->id, $equipment->location, $power_on ? '打开':'关闭');

        $equipment->is_using = $power_on;
        $equipment->user_using = $power_on ? $user : null;

        $equipment->save();
        if ($power_on) {
            // 关闭该仪器所有因意外未关闭的record
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
            $record->samples = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples');
            $record->save();

            $name = $user->name;
            $labs = Q("$user lab");
            if ($labs->total_count() == 1) {
                $name .= ' ('.$labs->current()->name.')';
            }

            // TODO 此方法需要清理, 虽然在 $data 中新加了值,
            // 但并未使用或保存 $data (如 $agent->call())
            // (Xiaopei Li@2013-12-01)
            $data = [
                'user' => $user->token,
                'name' => $name,
                'dtstart' => $record->dtstart,
            ];

            // ugly hack to add eq_reserv info to device_computer
            if (Module::is_installed('eq_reserv')) {
                if ($record->reserv->id) {
                    $data['reserv'] = [
                        'dtstart' => $record->reserv->dtstart,
                        'dtend' => $record->reserv->dtend
                    ];
                }
            }

        }
        else {
            $record =  Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
            if ($record->id) {
                $record->dtend = $now;

                if ($feedback = $data['feedback']) {
                    $record->status = $feedback['status'];
                    $record->feedback = $feedback['content'];
                    if ($feedback['project']) $record->project = O('lab_project', $feedback['project']);
                }
                //负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
                elseif ($record->user->is_allowed_to('管理使用', $equipment)) {
                    $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                }
                $record->samples = isset($feedback['samples']) ? (int) $feedback['samples'] : Config::get('eq_record.record_default_samples');
                $record->save();
            }
        }
    }

    function login_card($id, $card_no, $check_only = FALSE) {
        $this->_ready();

        $equipment = O('equipment', $id);
        if (!$equipment->id) throw new API_Exception('找不到对应的仪器!', 1002);

        if (!$card_no) throw new API_Exception(I18N::T('equipments', '该卡号找不到相应的用户'), 1003);

        $user = Event::trigger('get_user_from_sec_card', $card_no) ?: O('user', ['card_no' => $card_no]);
        if (!$user->id) {
            $card_no_s = (string)(($card_no + 0) & 0xffffff);
            $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ?: O('user', ['card_no_s' => $card_no_s]);
        }

        if (!$user->id) {
            $this->_log('卡号%s尝试失败', $card_no);
            throw new API_Exception(I18N::T('equipments', '该卡号找不到相应的用户'), 1003);
        }
        //end

        Cache::L('ME', $user); //store 'ME' into the cache
        $this->_log('卡号:%s => 用户:%s[%d]', $card_no, $user->name, $user->id);

        $this->_log('用户%s[%d]判断是否可开启仪器%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

        //要求打开仪器
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
            }
            else {
                throw new API_Exception(I18N::T('equipments', '您无权使用%equipment', ['%equipment'=>$equipment->name]), 1005);
            }
        }

        $this->_log('用户%s[%d]可以打开%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

        if (!$check_only) {
            $this->_switch_to(['equipment'=>$equipment, 'user'=>$user, 'power_on'=>TRUE]);
        }

        return ['username'=>$user->token, 'name'=>$user->name];
    }

    function login_username($id, $username, $password, $check_only = FALSE) {
        $this->_ready();

        $equipment = O('equipment', $id);
        if (!$equipment->id) throw new API_Exception('找不到对应的仪器!', 1002);

        $token = Auth::normalize($username);
        $user = O('user', ['token'=>$token]);
        if (!$user->id) {
            $this->_log('%s尝试登录, 但系统不存在该用户', $token);
            list($token, $backend) = Auth::parse_token($token);
            $backends = Config::get('auth.backends');
            $backend_title = $backends[$backend]['title'] ? I18N::T('people', $backends[$backend]['title']) : $backend;
            throw new API_Exception(I18N::T('equipments', '登录名%token找不到相应的用户', ['%token'=>implode('@', [$token, $backend_title])]), 1003);
        }

        $auth = new Auth($token);
        if (!$auth->verify($password)) {
            $this->_log('用户%s[%d]密码验证失败', $user->name, $user->id);
            throw new API_Exception(I18N::T('equipments', '密码验证失败, 请重新输入'), 1004);
        }

        $this->_log('用户%s[%d]判断是否可开启仪器%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

        //要求打开仪器
        //检测用户是否可以操作仪器
        if (!$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, Date::time())) {
            $this->_log('用户%s[%d]无权打开%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);
            $messages = Lab::messages(Lab::MESSAGE_ERROR);
            if (count($messages)) {
                //清空Lab::$messages,得到正确的错误提示
                Lab::$messages[Lab::MESSAGE_ERROR] = [];
                throw new API_Exception(I18N::T('equipments', join(' ', $messages)), 1005);
            }
            else {
                throw new API_Exception(I18N::T('equipments', '您无权使用%equipment', ['%equipment'=>$equipment->name]), 1005);
            }
        }

        $this->_log('用户%s[%d]可以打开仪器%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

        if (!$check_only) {
            $this->_switch_to(['equipment'=>$equipment, 'user'=>$user, 'power_on'=>TRUE]);
        }

        return ['username'=>$user->token, 'name'=>$user->name];
    }

    function logout_card($id, $card_no, $check_only = FALSE, $extra = []) {
        $this->_ready();

        $equipment = O('equipment', $id);
        if (!$equipment->id) throw API_Exception('找不到对应的仪器!', 1002);

        if (!$card_no) throw new API_Exception(I18N::T('equipments', '该卡号找不到相应的用户'), 1003);

        //苏大卡号获取学生信息
        $user = [];
        try{
            $user = Event::trigger('suda.get.user.bycard',$card_no);
        }catch (Exception $e){
            throw new API_Exception(T($e->getMessage()), 1003);
        }
        //如果未返回用户信息，说明非苏大。则走之前流程
        if(empty($user) || !$user){
            $user = Event::trigger('get_user_from_sec_card', $card_no) ?: O('user', ['card_no' => $card_no]);
        }

        if (!$user->id) {
            $card_no_s = (string)(($card_no + 0) & 0xffffff);
            $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ?: O('user', ['card_no_s' => $card_no_s]);
        }

        if (!$user->id) {
            $this->_log('卡号%s尝试失败', $card_no);
            throw new API_Exception(I18N::T('equipments', '该卡号找不到相应的用户'), 1003);
        }

        $this->_log('卡号:%s => 用户:%s[%d]', $card_no, $user->name, $user->id);
        $this->_log('用户%s[%d]判断是否可关闭仪器%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

        $now = Date::time();

        //要求关闭仪器
        if (!$user->is_allowed_to('管理使用', $equipment)) {
            $record = Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment][user=$user]:sort(dtstart D):limit(1)")->current();
            if (!$record->id) {
                // 没使用记录...  检查是否因为没有任何正在使用的记录
                if (Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment]")->total_count() > 0) {
                    $this->_log('用户%s[%d]无权关闭%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);
                    throw new API_Exception(I18N::T('equipments', '您无权关闭%equipment', ['%equipment'=>$equipment->name]), 1005);
                }
            }
        }

        if (!$check_only) {
            $this->_switch_to([
                'user'=>$user, 
                'power_on'=>FALSE, 
                'equipment'=>$equipment,
                'feedback' => $extra['feedback']
            ]);
        }

        $this->_log('用户%s[%d]可以关闭%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

        return true;
    }

    function breath($id, $data) {
        $this->_ready();

        $user = $data['user'];
        $uuid = $data['uuid'];
        $equipment = O('equipment', $id);
        if (!$equipment->id || $equipment->control_address != 'Biot://'.$uuid) throw new API_Exception('找不到对应的仪器!', 1002);

        $db = ORM_Model::db('equipment');
        $db->query('UPDATE `equipment` SET `is_using`=%d, `is_monitoring`=1, `is_monitoring_mtime`=%d WHERE `id`=%d', !!$user, time(), $id);
        return true;
    }

    function offline_record($id, $data) {
        $this->_ready();

        $equipment = O('equipment', $id);
        if (!$equipment->id) throw new API_Exception('找不到对应的仪器!', 1002);

        if ($data) {

            // 用户
            $card_no = (string) $data['card_no'];
            if ($card_no) {
                $user = Event::trigger('get_user_from_sec_card', $card_no) ?: O('user', ['card_no' => $card_no]);
                if (!$user->id) {
                    $card_no_s = (string)(($card_no + 0) & 0xffffff);
                    $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ?: O('user', ['card_no_s' => $card_no_s]);
                }

                if (!$user->id) {
                    $this->_log('[更新离线记录] 卡号 %s 找不到相应的用户', $card_no);
                    $user = O('user');
                }
                else {
                    $this->_log('[更新离线记录] 卡号:%s => 用户:%s[%d]', $card_no, $user->name, $user->id);
                }
            }
            else {
                $username = Auth::normalize($data['username']);
                $user = O('user', ['token'=>$username]);
                if (!$user->id) {
                    $this->_log('[更新离线记录] 登录名%s找不到相应的用户', $username);
                }
            }

            $time = (int) $data['time'];

            switch($data['status']) {
                case 'login':

                    // 关闭该仪器所有因意外未关闭的record
                    // 应该只有一条??
                    foreach (Q("eq_record[dtend=0][dtstart<=$time][equipment=$equipment]") as $record) {

                        // ??
                        if ($record->dtstart==$time) {
                            $record->delete();
                            continue;
                        }

                        $record->dtend = $time - 1;
                        $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                        $record->save();
                    }

                    /// 刷新对象
                    if ($user->id) {
                        $this->_log('[更新离线记录] %s[%d] 在 %s 登入仪器 %s[%d](%s)', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id, $equipment->location);
                    }
                    else {
                        $this->_log('[更新离线记录] 未知卡号 %s 在 %s 登入仪器 %s[%d](%s)', $card_no ?: '--', Date::format($time), $equipment->name, $equipment->id, $equipment->location);
                    }

                    $record = O('eq_record');
                    $record->dtstart = $time;
                    $record->dtend = 0;
                    $record->user = $user;
                    $record->equipment = $equipment;
                    $record->samples = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples');
                    if ($record->save()) {
                        $equipment->is_using = TRUE;
                        $equipment->save();
                    }

                    break;

                case 'logout':


                    if (!$user->id) {
                        // 尝试从最后一条记录中获得 $user

                        $record = Q("eq_record[dtstart<=$time][dtend=0][equipment=$equipment]:sort(dtstart D):limit(1)")->current();

                        if ($record->id) {
                            $user = $record->user;
                        }
                    }

                    if ($user->id) {

                        $record =  Q("eq_record[dtstart<$time][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();

                        if ($record->id) {
                            // 关闭这条记录

                            $this->_log('[更新离线记录] %s[%d] 在 %s 登出仪器 %s[%d](%s)', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id, $equipment->location);

                            $record->dtend = $time;

                            // 处理反馈
                            $feedback = @json_decode($data['feedback'], TRUE);
                            if (is_array($feedback)) {
                                if (isset(self::$feedback_status_map)) {
                                    $feedback_status = self::$feedback_status_map;
                                }

                                if (isset($feedback_status[($feedback['status'])])) {
                                    $feedback['status'] = $feedback_status[($feedback['status'])];
                                }

                                $record->status = (int)$feedback['status'];
                                $record->feedback = $feedback['feedback'];
                                $record->samples = max(Config::get('eq_record.record_default_samples'), (int)$feedback['samples']);
                                if ($feedback['project']) $record->project = $feedback['project'];
                            }
                            //负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
                            if ($record->status == EQ_Record_Model::FEEDBACK_NOTHING && $record->user->is_allowed_to('管理使用', $equipment)) {
                                $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                            }

                            if ($record->save()) {
                                $equipment->is_using = FALSE;
                                $equipment->save();
                            }
                        }
                        else {
                            // 找不到未关闭的记录
                            $this->_log('[更新离线记录] %s[%d] 在 %s 登出仪器 %s[%d](%s) 但没找到相应记录', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id, $equipment->location);
                        }
                    }
                    else {
                        // 未知卡号就不能登出了么?? 但找到了 $user 也未作权限判断??!!
                        $this->_log('[更新离线记录] 未知卡号 %s 在 %s 登出仪器', $card_no ?: '--', Date::format($time));
                    }
                break;
            case 'error':
                $this->_log('[更新离线记录] %s[%d] %s 在 %s 尝试 %s 登录失败', $user->name, $user->id, $card_no ?: '--', Date::format($time), $card_no ? '刷卡' : '密码');
                break;
            }
        }
        elseif (!$equipment->is_using) {
            // RPC 没有传 data (离线记录)?? (从 epc-server/epc.js 中看出不会有这样的 RPC)
            // 且仪器没有在用??

            // 关闭该仪器所有因意外未关闭的record
            $now = Date::time();
            foreach (Q("eq_record[dtend=0][dtstart<=$now][equipment=$equipment]") as $record) {
                if ($record->dtstart==$now) {
                    $record->delete();
                    continue;
                }

                $record->dtend = $now - 1;
                $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                $record->save();
            }

        }

        return $user->id ? ['username'=>$user->token, 'name'=>$user->name] : FALSE;
    }

    /*
     * cheng.liu@geneegrouop.com (应急需求)
     * 2016.1.11 获取固定时间段内已预约用户卡号对应的时间段表
     */
    function offline_reserv($uuid) {
        $this->_ready();

        $control_address = 'gmeter://'.$uuid;
        $equipment = O('equipment', ['control_address'=>$control_address]);
        if (!$equipment->id) throw new API_Exception('找不到对应的仪器!', 1002);

        /* 该仪器允许预约 */
        if (!$equipment->accept_reserv) { return false; }

        /* 该仪器允许用户在他人预约时段使用仪器（非预约段除外） */
        if ($equipment->unbind_reserv_time) { /* 暂时商量不予处理 */ }

        $dtstart = Date::get_day_start();
        $dtend = Date::next_time($dtstart, Config::get('gmeter.offline_reserv_day', 5));
        $ret = [];
        $reservs = Q("eq_reserv[equipment={$equipment}][dtstart=$dtstart~$dtend]");
        foreach ($reservs as $r) {
            $user = $r->user;
            if ($card_no = ($user->card_no ? : $user->get_card_no())) {
                !is_array($ret[$card_no])
                    and $ret[$card_no] = []
                    and $ret[$card_no]['time'] = [];
                $ret[$card_no]['info'] = [
                    'name' => $user->name,
                    'id' => $user->id,
                    'username' => $user->token
                ];
                $ret[$card_no]['time'][] = ['dtstart' => $r->dtstart, 'dtend' => $r->dtend];
            }
        }

        return $ret ?: false;

    }
}
