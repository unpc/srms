<?php

class API_Veronica extends API_Common {
    
    const FAKE_PROJECT_ID = -1;

    private function log() {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            $str = vsprintf($format, $args);
            Log::add(strtr('%name %str', [
                '%name' => '[Veronica API]',
                '%str' => $str,
            ]), 'devices');
        }
    }

    public function install ($data) {
        $this->_ready('equipments');

        $now = Date::time();

        $equipment = O('equipment', ['control_mode' => 'veronica', 'access_code' => trim($data['access_code'])]);

        if (!$equipment->id) {
            throw new API_Exception(I18N::T('equipments', '未找到对应仪器!'), 404);
            return FALSE;
        }

        $lifetime = Config::get('equipment.access_code_lifetime');

        if ($lifetime !== 0 && $equipment->access_code_ctime + $lifetime < $now) {
            throw new API_Exception(I18N::T('equipments', '验证码已过期!'), 400);
            return FALSE;
        }

        // 将安装时的验证码通过 sha1 加密, 作为控制地址(唯一标识), Veronica 客户端也需要做同样的操作
        $control_address = sha1($equipment->access_code);

        if ($control_address != trim($data['control_address'])) {
            throw new API_Exception(I18N::T('equipments', '控制地址不符!'), 400);
            return FALSE;
        }
        
        $equipment->control_address = $control_address;
        $equipment->access_code_ctime = 0;
        $equipment->control_mode = 'veronica';
        
        if(!$equipment->save()){
            throw new API_Exception(I18N::T('equipments', '更新仪器信息失败!'), 500);
            return FALSE;
        }

        // Super Key 机制见 README

        // TODO 自动分发更新

        $this->log(I18N::T('equipments', '仪器 %s[%d]已安装'), $equipment->name, $equipment->id);
        
        return TRUE;
    }

    public function connect ($data) {
        $this->_ready('equipments');

        $equipment = O('equipment', ['control_mode' => 'veronica', 'control_address' => trim($data['control_address'])]);

        if (!$equipment->id) {
            throw new API_Exception(I18N::T('equipments', '未找到对应仪器!'), 404);
            return FALSE;
        }

        $equipment->server = $data['rest'];
        $equipment->connect = true;
        $equipment->veronica = [
            'os' => $data['os'],
            'version' => $data['version'],
        ];

        if(!$equipment->save()){
            throw new API_Exception(I18N::T('equipments', '更新仪器信息失败!'), 500);
            return FALSE;
        }

        $res = [];
        // 返回值: 仪器基本信息
        $res['equipment_id'] = $equipment->id;
        $res['equipment_name'] = $equipment->name;

        // 返回值: 人员 backends
        $res['backends'] = [];
        $backends = Config::get('auth.backends');
        $i = 1;
        foreach ($backends as $k => $o) {
            $res['backends'][$k] = I18N::T('people', $o['title']);
            $i++;
        }
        $res['default_backend'] = Config::get('auth.default_backend');

        // 返回值: 离线卡号(管理员)
        $cards = [];
        $free_access_cards = $equipment->get_free_access_cards();
        foreach($free_access_cards as $card_no => $u) {
            $cards[$card_no] = $card_no;
        }
        $res['cards'] = array_values($cards);

        // 返回值: 使用类型(配置)
        if (Config::get('equipment.enable_use_type')) $res['use_type'] = EQ_Record_Model::$use_type;
        $res['is_evaluate'] = Module::is_installed('eq_evaluate') ? 1 : 0;
        $res['qrcode'] = URI::url('http://wx.17kong.com/equipment/'.$equipment->yiqikong_id);

        // 获取登陆的自定义表单
        $extra = Extra_Model::fetch($equipment, 'use');
        $login = new ArrayIterator(Yiqikong_Extra::format($extra));
        Event::trigger('veronica.extra.login.view', $login, $equipment);
        $res['view']['login'] = $login->getArrayCopy();

        $logout = new ArrayIterator();
        Event::trigger('veronica.extra.logout.view', $logout, $equipment);
        $res['view']['logout'] = $logout->getArrayCopy();

        $this->log(I18N::T('equipments', '仪器 %s[%d] 已连接'), $equipment->name, $equipment->id);
        return $res;
    }

    public function login ($data) {
        $this->_ready('equipments');

        $equipment = O('equipment', ['control_mode' => 'veronica', 'control_address' => trim($data['control_address'])]);

        if (!$equipment->id) {
            throw new API_Exception(I18N::T('equipments', '未找到对应仪器!'), 404);
            return FALSE;
        }

        if ($data['card_no']) {
            $card_no = trim($data['card_no']);
            $user = O('user', ['card_no' => $card_no]);

            if (!$user->id) {
                $card_no_s = (string)(($card_no + 0) & 0xffffff);
                $user = O('user', ['card_no_s' => $card_no_s]);
            }

            if (!$user->id) {
                $this->log(I18N::T('equipments', '卡号%s尝试登录失败，该卡号找不到相应的用户!'), $card_no);
                throw new API_Exception(I18N::T('equipments', '该卡号找不到相应的用户!'), 404);
                return FALSE;
            } else {
                $this->log(I18N::T('equipments', '用户 %s[%d] 尝试使用卡号 %s 登录仪器 %s[%d]!'), $user->name, $user->id, $card_no, $equipment->name, $equipment->id);
            }
        } else {
            $token = trim($data['token']);
            $user = O('user', ['token' => $token]);

            if ($user->id) {
                $auth = new Auth($token);

                // TODO 使用账号密码登录，现在明文传输的密码，之后可以考虑引入简单加密规则
                if (!$auth->verify($data['password'])) {
                    $this->log(I18N::T('equipments', '用户 %s[%d] 尝试使用账号 %s 登录仪器 %s[%d]失败! 账号密码不匹配'), $user->name, $user->id, $token, $equipment->name, $equipment->id);
                    throw new API_Exception(I18N::T('equipments', '帐号和密码不匹配!'), 401);
                    return FALSE;
                }
                $this->log(I18N::T('equipments', '用户 %s[%d] 尝试使用账号 %s 登录仪器 %s[%d]!'), $user->name, $user->id, $token, $equipment->name, $equipment->id);
            } else {
                $this->log(I18N::T('equipments', '账号%s尝试登录失败，该账号找不到相应的用户!'), $token);
                throw new API_Exception(I18N::T('equipments', '未找到该账号对应的用户!'), 404);
                return FALSE;
            }
        }

        if ($user->atime == 0) {
            $this->log(I18N::T('equipments', '%s[%d]尝试登录，但此账号未激活'), $user->name, $user->id);
            throw new API_Exception(I18N::T('equipments', '用户未激活!'), 401);
            return FALSE;
        }

        if ($user->dto != 0 && $user->dto < Date::time()) {
            $this->log(I18N::T('equipments', '%s[%d]尝试登录，但此账号已过期'), $user->name, $user->id);
            throw new API_Exception(I18N::T('equipments', '用户已过期!'), 401);
            return FALSE;
        }

        if (!$user->is_allowed_to('管理使用', $equipment) && 
            $equipment->cannot_access($user, Date::time())) {
            $this->log(I18N::T('equipments', '用户%s[%d]无权使用仪器%s[%d]'), $user->name, $user->id, $equipment->name, $equipment->id);

            $messages = Lab::messages(Lab::MESSAGE_ERROR);
            if (count($messages)) {
                throw new API_Exception(join(' ', array_map(function($msg) {
                    return I18N::T('equipments', $msg);
                }, $messages)), 401);
            } else {
                throw new API_Exception(I18N::T('equipments', '您无权使用%equipment', [
                    '%equipment'=>$equipment->name
                ]), 401);
            }
            return FALSE;
        }

        $res = [
            'token' => $user->token,
            'name' => $user->name,
            'next' => 'switch_on'
        ];

        return $res;
    }

    public function logout ($data) {
        $this->_ready('equipments');

        $equipment = O('equipment', ['control_mode' => 'veronica', 'control_address' => trim($data['control_address'])]);

        if (!$equipment->id) {
            throw new API_Exception(I18N::T('equipments', '未找到对应仪器!'), 404);
            return FALSE;
        }

        $user = O('user', ['token' => trim($data['token'])]);

        if (!$user->id) {
            //直接关闭
            return [
                'token' => $user->token,
                'name' => $user->name,
                'next' => 'switch_off'
            ];
        }
            
        Cache::L('ME', $user);
        $now = Date::time();

        if (!$user->is_allowed_to('管理使用', $equipment)) {
            $record = Q("eq_record[dtend=0][equipment=$equipment][user=$user]")->current();
            if (!$record->id && Q("eq_record[dtend=0][equipment=$equipment]")->total_count() > 0) {
                $this->log(I18N::T('equipments', '用户%s[%d]无权关闭仪器%s[%d]'), $user->name, $user->id, $equipment->name, $equipment->id);
                throw new API_Exception(I18N::T('equipments', '您无权关闭该仪器!'), 401);
                return FALSE;
            }
        }

        // 样品数是否必填
        if (Config::get('eq_record.glogon_require_samples')) {
            if (Config::get('equipment.feedback_samples_allow_zero', FALSE)) {
                if ($data['samples'] < 0) {
                    throw new API_Exception(I18N::T('equipments', '样品数必须为大于或等于0的整数!'), 400);
                    return FALSE;
                }
            } else {
                if (!$data['samples']) {
                    throw new API_Exception(I18N::T('equipments', '样品数必须为大于0的整数!'), 400);
                    return FALSE;
                }
            }
        }

        if (class_exists('Lab_Project_Model')) {
            //如果为fake的数据, 表示与对应的预约记录的关联项目相同
            $project_id = trim($data['project']);
            if ($project_id == self::FAKE_PROJECT_ID && Module::is_installed('eq_reserv')) {
                $dtstart = Q("eq_record[user={$user}][equipment={$equipment}][dtend=0]")->current()->dtstart;
                $dtend = Date::time();
                $reserv = Q("eq_reserv[user={$user}][equipment={$equipment}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}]")->current();
                $project_id = $reserv->project->id ? : $project_id;
            }

            $project = O('lab_project', $project_id);
            $status = Lab_Project_Model::STATUS_ACTIVED;
            $count = Q("$user lab lab_project[status={$status}]")->total_count();
            $must_connect_project = Config::get('eq_record.must_connect_lab_project');

            if ($must_connect_project && $count && !$project->id) {
                throw new API_Exception(I18N::T('equipments', '请选择关联项目!'), 400);
                return FALSE;
            }
        }

        Event::trigger('equipments.veronica.logout', $data, $user, $equipment, $record);

        $res = [
            'token' => $user->token,
            'name' => $user->name,
            'next' => 'switch_off'
        ];

        return $res;
    }

    public function offline_password ($data) {
        $this->_ready('equipments');

        $equipment = O('equipment', ['control_mode' => 'veronica', 'control_address' => trim($data['control_address'])]);

        if (!$equipment->id) {
            throw new API_Exception(I18N::T('equipments', '未找到对应仪器!'), 404);
            return FALSE;
        }
    
        if(!$equipment->offline_password) {
            $equipment->offline_password = Misc::random_password(6, 1);
            $equipment->save();

            Equipments::send_offline_password_init($equipment);
        }

        $res = [
            'equipment_id' => $equipment->id,
            'equipment_name' => $equipment->name,
            'offline_password' => $equipment->offline_password,
        ];

        return $res;
    }

    public function switch_on ($data) {
        $this->_ready('equipments');
        
        $now = Date::time();

        $equipment = O('equipment', ['control_mode' => 'veronica', 'control_address' => trim($data['control_address'])]);

        if (!$equipment->id) {
            throw new API_Exception(I18N::T('equipments', '未找到对应仪器!'), 404);
            return FALSE;
        }

        $user = O('user', ['token' => trim($data['token'])]);

        Event::trigger('veronica.extra.login.validate', $data, $user, $equipment);

        //表单验证放到swotch_on
        $form = Form::filter([]);
        unset($data['extra']['#']);
        $form['extra_fields'] = $data['extra'];
        if (isset($form['extra_fields'])) Extra::validate_extra_value(null, $equipment, 'use', $form);
        if (!$form->no_error) throw new API_Exception(join(' ', array_column($form->errors, 0)));

        $equipment->is_using = TRUE;

        $equipment->save();

        foreach (Q("eq_record[dtend=0][dtstart<=$now][equipment=$equipment]") as $record) {
            if ($record->dtstart == $now) {
                $record->delete();
                continue;
            }
            $record->dtend = $now - 1;
            $record->save();
        }

        $record = O('eq_record');
        $record->is_computer_device = TRUE;
        $record->dtstart = $now;
        $record->dtend = 0;
        $record->user = $user;
        $record->equipment = $equipment;
        $record->samples = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples');
        
        if (Config::get('equipment.enable_use_type')) {
            $record->use_type = $data['use_type'] ? : EQ_Record_Model::USE_TYPE_USING;
            $record->use_type_desc = $data['use_type_desc'];
        }


        Event::trigger('veronica.extra.switch_on.before', $data, $record);
        $record->save();
        Event::trigger('veronica.extra.switch_on.after', $data, $record);

        unset($data['extra']['#']);
        $extra_value = O('extra_value', ['object' => $record]);
        $extra_value->object = $record;
        $extra_value->values = $data['extra'];
        $extra_value->save();

        $name = $user->name;
        $labs = Q("$user lab");
        if ($labs->total_count() == 1) {
            $name .= ' ('.$labs->current()->name.')';
        }

        $res = [
            'user' => $user->token,
            'name' => $name,
            'dtstart' => $now,
            'record_id' => $record->id
        ];

        if (Module::is_installed('eq_reserv')) {
            if ($record->reserv->id) {
                $res['reserv'] = [
                    'dtstart' => $record->reserv->dtstart,
                    'dtend' => $record->reserv->dtend
                ];
            }
        }

        return $res;
    }

    public function switch_off ($data) {
        $this->_ready('equipments');
        $now = Date::time();

        $equipment = O('equipment', ['control_mode' => 'veronica', 'control_address' => trim($data['control_address'])]);
        if (!$equipment->id) throw new API_Exception(I18N::T('equipments', '未找到对应仪器!'), 404);
        $equipment->is_using = FALSE;
        $equipment->save();

        $record =  Q("eq_record[dtend=0][equipment={$equipment}]:limit(1)")->current();
        if (!$record->id) throw new API_Exception(I18N::T('equipments', '未找到对应使用记录!'), 404);

        $user = $record->user;
        $record->dtend = $now;

        $status = array_keys(EQ_Record_Model::$status_type);
        if (!in_array(trim($data['status']), $status)) {
            throw new API_Exception(I18N::T('equipments', '未找到对应仪器状态!'), 404);
            return FALSE;
        }

        $record->status = (int)$data['status'];
        $record->feedback = $data['feedback'];

        if ($data['samples'] >= 0) {
            $record->samples = max(0, (int)$data['samples']);
        }

        if ($data['project']) {
            $project = O('lab_project', trim($data['project']));
            $record->project = $project;
        }

        if (Config::get('eq_record.duty_teacher') && $record->equipment->require_dteacher) {
            $duty_teacher = O('user', $data['duty_teacher']);
            $record->duty_teacher = $duty_teacher;
        }

        Event::trigger('veronica.extra.switch_off.before', $data, $record);
        $record->save();
        Event::trigger('veronica.extra.switch_off.after', $data, $record);

        $name = $user->name;
        $labs = Q("$user lab");
        if ($labs->total_count() == 1) {
            $name .= ' ('.$labs->current()->name.')';
        }

        $res = [
            'user' => $user->token,
            'name' => $name,
            'dtstart' => $record->dtstart,
            'dtend' => $now,
        ];

        return $res;
    }

    public function disconnect ($data) {
        $this->_ready('equipments');

        $equipment = O('equipment', ['control_mode' => 'veronica', 'control_address' => trim($data['control_address'])]);

        $equipment->connect = false;

        if ($equipment->save()) {
            return TRUE;
        } else {
            throw new API_Exception(I18N::T('equipments', '仪器联网状态更新失败!'), 500);
            return FALSE;
        }
    }

    public function info ($data) {
        // 课题组项目
        $this->_ready('equipments');
        $res = [];

        $equipment = O('equipment', ['control_mode' => 'veronica', 'control_address' => trim($data['control_address'])]);
        
        if (!$equipment->id) {
            throw new API_Exception(I18N::T('equipments', '未找到对应仪器!'), 404);
            return FALSE;
        }

        $user = O('user', ['token' => trim($data['token'])]);
        if (!$user->id) {
            throw new API_Exception(I18N::T('equipments', '未找到对应的用户!'), 404);
            return FALSE;
        }

        if (!Q("$user lab")->total_count()) {
            throw new API_Exception(I18N::T('equipments', '未找到对应的课题组!'), 404);
            return FALSE;
        }

        $projects = [];
        if (Module::is_installed('labs')) {
            $status = Lab_Project_Model::STATUS_ACTIVED;
            $lab = Q("$user lab")->current();
            $projects = Q("lab_project[status={$status}][lab=$lab]:sort(id A)")->to_assoc('id', 'name');

            $total_count = count($projects);

            if ($total_count) {
                $projects[0] = I18N::T('equipments', '请选择此次仪器服务的项目');
                ksort($projects);
            }

            if (Module::is_installed('eq_reserv')) {
                $record = Q("eq_record[equipment={$equipment}][dtend=0]:limit(1)")->current();
                $dtstart = $record->dtstart;
                $reserv = Q("eq_reserv[user={$user}][equipment={$equipment}][dtstart~dtend={$dtstart}]:limit(1)")->current();
                $project = $reserv->project;
                $pid = $project->id;
            }


            if ($total_count) {
                foreach ($projects as $k => $p) {
                    $projects[$k] = $p;
                }
                if ($pid) {
                    unset($projects[$pid]);
                    $projects = [self::FAKE_PROJECT_ID => $project->name] + $projects;
                }
            } else {
                $message = I18N::T('equipments', '您课题组尚无项目, 请联系负责人添加项目!');
            }

        }

        $res['must_connect_lab_project'] = Config::get('eq_record.must_connect_lab_project') ? 1 : 0;
        $res['data'] = $projects;

        $res['default_samples'] = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples');

        // 获取登陆的自定义表单
        $extra = Extra_Model::fetch($equipment, 'use');
        $login = new ArrayIterator(Yiqikong_Extra::format($extra));
        Event::trigger('veronica.extra.login.view', $login, $equipment);
        $res['view']['login'] = $login->getArrayCopy();

        $logout = new ArrayIterator();
        Event::trigger('veronica.extra.logout.view', $logout, $equipment);
        $res['view']['logout'] = $logout->getArrayCopy();
        return $res;

    }

    public function offline_record ($data) {
        
        $this->_ready('equipments');
        $res = [];
        
        $status_type = array_keys(EQ_Record_Model::$status_type);

        foreach ($data as $val) {

            $operate = $val['operate'];
            if (!in_array(trim($val['status']), $status_type)) {
                throw new API_Exception(I18N::T('equipments', '未找到对应仪器状态!'), 404);
                return FALSE;
                break;
            }
            if ($val['card_no']) {
                $card_no = trim($val['card_no']);
                $user = O('user', ['card_no' => $card_no]);
    
                if (!$user->id) {
                    $card_no_s = (string)(($card_no + 0) & 0xffffff);
                    $user = O('user', ['card_no_s' => $card_no_s]);
                }
    
                if (!$user->id) {
                    $this->log(I18N::T('equipments', '[更新离线记录] 卡号 %s 找不到相应的用户'), $card_no);
                } else {
                    $this->log(I18N::T('equipments', '[更新离线记录] 卡号:%s => 用户:%s[%d]'), $card_no, $user->name, $user->id);
                }
            } else {
                $token = trim($val['token']);
                $user = O('user', ['token' => $token]);

                if (!$user->id) {
                    $this->log(I18N::T('equipments', '[更新离线记录] 登录名%s找不到相应的用户'), $token);
                }
            }

            $equipment = O('equipment', ['control_mode' => 'veronica', 'control_address' => trim($val['control_address'])]);
                    
            if (!$equipment->id) {
                throw new API_Exception(I18N::T('equipments', '未找到对应仪器!'), 404);
                return FALSE;
                break;
            }

            switch($operate) {
                case 'logout':  //在线登录时,离线登出
                    $record = O('eq_record', $val['record_id']);
                    $record->dtend = $val['dtend'];
                    $record->status = $val['status'];
                    $record->feedback = $val['feedback'];
                    $record->samples = $val['samples'];
                    if ($val['project']) $record->project = O('lab_project', $val['project']);
                    Event::trigger('veronica.extra.logout.offline.before', $val, $record);
                    if ($record->save()) {
                        Event::trigger('veronica.extra.logout.offline.after', $val, $record);
                        $res[] = $val['id'];
                    }
                    break;
                    
                case 'login'://离线登录，在线登出
                    $start = $val['dtstart'] - 2;
                    //如果存在在这条记录开始之前还未闭合的记录先关闭那条记录再上传
                    $record =  Q("eq_record[dtstart<={$start}][dtend=0][equipment={$equipment}]")->current();    
                    if ($record->id) {
                        $record->dtend = $val['dtstart'] - 1;
                        $record->save();
                    }
                    
                    $record = O('eq_record');
                    $record->equipment = $equipment;
                    if ($user->id) $record->user = $user;
                    $record->dtstart = $val['dtstart'];
                    Event::trigger('veronica.extra.login.offline.before', $val, $record);
                    if ($record->save()) {
                        Event::trigger('veronica.extra.login.offline.after', $val, $record);
                        $res[] = $val['id'];
                    }
                    break;

                case 'offline'://离线登录和登出
                    $record = O('eq_record');
                    $record->equipment = $equipment;
                    if ($user->id) $record->user = $user;
                    $record->dtstart = $val['dtstart'];
                    $record->dtend = $val['dtend'];
                    $record->status = $val['status'];
                    $record->feedback = $val['feedback'];
                    $record->samples = $val['samples'];
                    if ($val['project']) $record->project = O('lab_project', $val['project']);

                    Event::trigger('veronica.extra.login.offline.before', $val, $record);
                    Event::trigger('veronica.extra.logout.offline.before', $val, $record);
                    if ($record->save()) {
                        Event::trigger('veronica.extra.login.offline.after', $val, $record);
                        Event::trigger('veronica.extra.logout.offline.after', $val, $record);
                        $res[] = $val['id'];
                    };
                    break;
            }
        }
        return $res;
    }

    public function offline_reserv ($data) {
        $this->_ready('equipments');

        $equipment = O('equipment', ['control_mode' => 'veronica', 'control_address' => trim($data['control_address'])]);
        
        if (!$equipment->id) {
            throw new API_Exception(I18N::T('equipments', '未找到对应仪器!'), 404);
            return FALSE;
        }

        if (!$equipment->accept_reserv) {
            throw new API_Exception(I18N::T('equipments', '此仪器无需预约!'), 400);
            return FALSE;
        }

        $dtstart = Date::get_day_start();

        $dtend = Date::next_time($dtstart, Config::get('glogon.offline_reserv_day', 5));

        $res = [];

        $reserv = Q("eq_reserv[equipment={$equipment}][dtstart=$dtstart~$dtend]");

        foreach ($reserv as $r) {
            $user = $r->user;
            if ($card_no = $user->card_no) {
                !is_array($res[$card_no]) and $res[$card_no] = [];
                $res[$card_no]['reservs'][] = ['dtstart' => $r->dtstart, 'dtend' => $r->dtend];
                $res[$card_no]['token'] = $user->token;
                $res[$card_no]['name'] = $user->name;
                $res[$card_no]['lab'] = Q("{$user} lab")->current()->name;
            }
        }

        return $res;
    }
}