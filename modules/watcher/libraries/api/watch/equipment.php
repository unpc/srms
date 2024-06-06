<?php

class API_Watch_Equipment {

    public static $errors = [
        1001 => '请求来源地址不合法!',
        1002 => '请求服务器未授权!',
        1003 => '请求参数错误!',
        1004 => '请求资源状态异常!',
        1005 => '找不到对应的仪器信息!'
    ];

    public static $feedback_status_map = [
        1 => EQ_Record_Model::FEEDBACK_NORMAL,
        -1 => EQ_Record_Model::FEEDBACK_PROBLEM,
        0 => EQ_Record_Model::FEEDBACK_NOTHING
    ];

    private function _log() {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            $str = vsprintf($format, $args);
            Log::add(strtr('%name %str', [
                        '%name' => '[Watcher API]',
                        '%str' => $str,
            ]), 'watcher');
        }
    }

    private function _ready($client_id, $client_secret)
    {
        /* 1. 如果设置了ip限制，将会对其客户来源ip进行限制 (非必须)*/
        /*
        $whitelist = Config::get('api.rpc.source', []);
        if (count($whitelist)) {
            if (!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                throw new API_Exception(self::$errors[1001], 1001);
            }
        }
        */
        /* 2. 验证接口使用者是否存在我方许可颁发的client_id and client_secret (强制验证) */
        $provides = Config::get('rpc.identity');
        if ($provides[$client_id]['secret'] != $client_secret) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        return;
    }

    private function _getEquipment($uuid='') {
        if (!$uuid) return FALSE;

        $equipment = O('equipment', ['control_mode' => 'computer', 'control_address' => $uuid]);
        if (!$equipment->id) {
            $equipment = O('equipment', ['control_mode' => 'power', 'control_address' => 'gmeter://'.$uuid]);
        }
        if (!$equipment->id) {
            $equipment = O('equipment', $uuid);
        }
        if (!$equipment->id) {
            $equipment = O('equipment', ['yiqikong_id' => $uuid]);
        }

        return $equipment;
    }

    public function validate($params = []) {
        $this->_ready($params['client_id'], $params['client_secret']);
        if (!is_numeric(H($params['code']))) throw new API_Exception(self::$errors[1003], 1003);

        $equipment = O('equipment', ['watcher_code' => H($params['code'])]);
        if (!$equipment->id) throw new API_Exception(self::$errors[1005], 1005);
        $equipment->control_mode = 'ultron';
        $equipment->server = $params['server'];
        $equipment->control_address = $params['macAddress'];
        $equipment->connect = 1;
        $equipment->save();

        $client = O('eq_client', [
            'equipment' => $equipment,
            'mac_addr' => $params['macAddress'],
        ]);
        return (bool)$client->id;
    }

    public function equipmentClient($params = []) {
        $this->_ready($params['client_id'], $params['client_secret']);

        if (!is_numeric(H($params['code']))) throw new API_Exception(self::$errors[1003], 1003);

        $equipment = O('equipment', ['watcher_code' => H($params['code'])]);
        if (!$equipment->id) throw new API_Exception(self::$errors[1005], 1005);

        $eq_client = O('eq_client', ['equipment' => $equipment, 'mac_addr' => $params['macAddress']]);

        if ($params['action'] == 'delete') {
            if (!$eq_client->id) throw new API_Exception(self::$errors[1003], 1003);
            return $eq_client->delete();
        }
        else {
            if (!$eq_client->id) {
                $eq_client = O('eq_client');
            }
            $eq_client->equipment = $equipment;
            $eq_client->mac_addr = $params['macAddress'];
    
            if ($eq_client->save()) {
                return [
                    'id' => $eq_client->id,
                    'equipmentId' => $equipment->id,
                    'clientId' => $eq_client->id
                ];
            }
        }
    }

    /*
    * cheng.liu@geneegroup.com 2016.4.28
    * description   观察者模式获取仪器的UUID，通过绑定的Code
    * params    uuid <id or yiqikong_id>
    * return    [equipment UUID]
    */
    public function getEquipmentUUID($params=[])
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        if (!is_numeric(H($params['code']))) throw new API_Exception(self::$errors[1003], 1003);
        $equipment = O('equipment', ['watcher_code' => H($params['code'])]);
        if (!$equipment->id) throw new API_Exception(self::$errors[1005], 1005);
        
        return [
            'uuid' => Watcher_Equipment::get_uuid($equipment),
            'code' => H($equipment->watcher_code)
        ];

    }

    /*
    * cheng.liu@geneegroup.com 2016.4.5
    * description   观察者模式获取Equipment对象下的基本信息
    * params    uuid <id or yiqikong_id>
    * return    [equipment info array]
    */
    public function info($params=[]) 
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);
        $root = Tag_Model::root('equipment');
        $contacts = Q("{$equipment} user.contact")->to_assoc('id', 'name');
        $incharges = Q("{$equipment} user.incharge")->to_assoc('id', 'name');
        $tags = Q("{$equipment} tag_equipment[root=$root]")->to_assoc('id', 'name');
        $now = Date::time();

        $icon_file = Core::file_exists(PRIVATE_BASE.'icons/equipment/128/'.$equipment->id.'.png', '*');
        if ($icon_file) $icon_url = Config::get('system.base_url').'icon/equipment.'.$equipment->id.'.128';
        $icon_url = $icon_url ? : '';
        
        if (!$equipment->yiqikong_id) {
            $str = $LAB_ID . ':' . $equipment->id;
            $equipment->yiqikong_id = hash_hmac('sha1', $str, self::ROUTINGKEY_DIRECTORY);
        }

        $data = [
            'id' => $equipment->id,
            'yiqikong_id' => $equipment->yiqikong_id,
            'icon_url' => $icon_url,
            'url' => $equipment->url(),
            'name' => H($equipment->name),
            'phone' => H($equipment->phone),
            'email' => H($equipment->email),
            'ref_no' => H($equipment->ref_no),
            'cat_no' => H($equipment->cat_no),
            'model_no' => H($equipment->model_no),
            'price' => (float)$equipment->price,
            'control_mode' => H($equipment->control_mode),
            'current_user' => H($equipment->current_user()->name),
            'accept_sample' => (int)$equipment->accept_sample,
            'accept_reserv' => (int)$equipment->accept_reserv,
            'accept_limit_time' =>  (int)$equipment->accept_limit_time,
            'reserv_url' => $equipment->url('reserv'),
            'sample_url' => $equipment->url('sample'),
            'manufacturer' => H($equipment->manufacturer),
            'organization' => H($equipment->organization),
            'specification' => H($equipment->specification),
            'tech_specs' => H($equipment->tech_specs),
            'features' => H($equipment->features),
            'configs' => H($equipment->configs),
            'incharges' =>join(', ', $incharges),
            'time' => $now
        ];

        if ($equipment->charge_setting) {
        $data['charge_setting'] = I18N::T('按需收费', 'equipments');
        }
        else {
        $data['charge_setting'] = I18N::T('未设置收费', 'equipments');
        }

        return $data;
    }

    public function usedInfo($params=[]) 
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);
        $now = Date::time();
        return [
            'total_used_time' => Q("eq_record[equipment={$equipment}][dtstart<={$now}][dtend>0][dtstart<@dtend]")->SUM('dtend') - Q("eq_record[equipment={$equipment}][dtstart<={$now}][dtend>0][dtstart<@dtend]")->SUM('dtstart'),
            'total_time' => Q("eq_record[equipment={$equipment}][dtstart<=$now]")->total_count(),
            'time' => $now
        ];
    }

    public function usedStatus($params=[])
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);
        $now = Date::time();

        $status = [];

        $record = Q("eq_record[equipment={$equipment}][dtstart<{$now}][dtend=0]:sort(dtend D):limit(1)")->current();
        if ($record->id) {
            $user = $record->user;
            $reserv = Q("eq_reserv[equipment={$equipment}][dtstart<={$now}][dtend>={$now}]")->current();
            $data = [
                'uid' => $user->id,
                'uname' => H($user->name),
                'icon_url' => $user->icon_url('64'),
                'is_admin' => (int)$user->is_allowed_to('管理使用', $equipment)
            ];
            if ($reserv->id) {
                $data['reserv'] = [
                    'dtstart' => $reserv->dtstart,
                    'dtend' => $reserv->dtend
                ];
                $ruser = $reserv->user;
                $times = Q("eq_record[reserv={$reserv}][dtend>0]")->SUM('dtend - dtstart');
                if ($ruser->id == $user->id) {
                    $times += ($now - $record->dtstart);
                }
                else {
                    $data['reserv']['uname'] = H($reserv->user->name);
                }
                $data['reserv']['used_time'] = $times;
                $data['reserv']['surplus_time'] = $reserv->dtend - $now;
            }
            if (!$GLOBALS['preload']['people.multi_lab']) {
                $lab = Q("$user lab")->current();
                $data['lab'] = H($lab->name);
            }
            else {
                $data['lab'] = H($reserv->project->lab->name);
            }
            $status['current'] = $data;
        }
        

        $reserv = Q("eq_reserv[equipment={$equipment}][dtstart>={$now}]:sort(dtstart)")->current();
        if ($reserv->id) {
        $status['next'] = [
            'uname' => H($reserv->user->name),
            'dtstart' => $reserv->dtstart,
            'dtend' => $reserv->dtend
        ];
        if (!$GLOBALS['preload']['people.multi_lab']) {
            $lab = Q("{$reserv->user} lab")->current();
            $status['next']['lab'] = H($lab->name);
        }
        else {
            $status['next']['lab'] = H($reserv->project->lab->name);
        }
        }
        
        $record = Q("eq_record[equipment={$equipment}][dtend>0][dtstart<{$now}]:sort(dtend D):limit(1)")->current();
        if ($record->id) {
        $status['before'] = [
            'uname' => H($record->user->name),
            'phone' => $record->user->phone,
        ];
        if (!$GLOBALS['preload']['people.multi_lab']) {
            $lab = Q("{$record->user} lab")->current();
            $status['before']['lab'] = H($lab->name);
        }
        else {
            $status['before']['lab'] = H($record->project->lab->name);
        }
        }
        
        $status['is_using'] = H($equipment->is_using);
        $status['status'] = H($equipment->status);

        return $status;

    }


    /*
    * cheng.liu@geneegroup.com 2016.4.5
    * description   观察者模式获取Equipment预约信息
    * params    uuid <id or yiqikong_id>
    * return    [reserves array]
    */
    public function reservList($params=[])
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);
        $now = Date::time();
        $dtstart = Date::get_week_start($now);
        $dtend = Date::get_week_end($now);
        $reservs = Q("eq_reserv[equipment={$equipment}][dtstart>={$dtstart}][dtend<={$dtend}]");
        $data = [];
        foreach ($reservs as $reserv) {
            $data[] = [
                'id' => $reserv->id,
                'title' => H($reserv->component->name),
                'start' => $reserv->dtstart,
                'end' => $reserv->dtend,
                'uid' => $reserv->user->id,
                'uname' => H($reserv->user->name)
            ]; 
        }

        return $data;
    }

    /*
    * cheng.liu@geneegroup.com 2016.4.5
    * description   观察者模式获取Equipment使用信息
    * params    uuid <id or yiqikong_id>
    * return    [records array]
    */
    public function recordList($params=[])
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $page = (int)$params['page'] ?: 1;
        $step = (int)$params['pageCount'] ?: 20;
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);
        $now = Date::time();
        $start = ($page - 1 ) * $step;
        $records = Q("eq_record[equipment={$equipment}][dtend>=0][dtend<={$now}]:sort(dtstart D):limit({$start}, {$step})");
        $data = [];
        foreach ($records as $record) {
            $c = O('eq_charge', ['source' => $record]);
            if ($GLOBALS['preload']['people.multi_lab']) {
                $lab = $record->project->lab;
            }
            else {
                $lab = Q("$user lab")->current();
            }
            $data[] = [
                'id' => $record->id,
                'no' => str_pad($record->id, 6, 0, STR_PAD_LEFT),
                'uname' => H($record->user->name),
                'lab' => H($lab->name),
                'dtstart' => $record->dtstart,
                'dtend' => $record->dtend,
                'sample' => (int)$record->samples,
                'amount' => $c->id ? $c->amount : 0,
                'auto_amount' => $c->id ? $c->auto_amount : 0
            ];
        }

        return $data;
    }

    /*
    * cheng.liu@geneegroup.com 2016.4.5
    * description   观察者模式获取Equipment送样记录信息
    * params    uuid <id or yiqikong_id>
    * return    [sample array]
    */
    public function sampleList($params=[])
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $page = (int)$params['page'] ?: 1;
        $step = (int)$params['pageCount'] ?: 20;
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);
        $now = Date::time();
        $start = ($page - 1 ) * $step;
        $data = [];
        $samples = Q("eq_sample[equipment={$equipment}][dtsubmit>0]:sort(dtsubmit D):limit({$start}, {$step})");
        foreach ($samples as $sample) {
            $c = O('eq_charge', ['source' => $sample]);
            $data[] = [
                'id' => $sample->id,
                'no' => str_pad($sample->id, 6, 0, STR_PAD_LEFT),
                'uname' => H($sample->sender->name),
                'lab' => H($sample->lab->name),
                'dtsubmit' => $sample->dtsubmit,
                'count' => $sample->count,
                'amount' => $c->id ? $c->amount : '0.00',
                'auto_amount' => $c->id ? $c->auto_amount : '0.00'
            ];
        }
        return $data;
    }

    /*
    * cheng.liu@geneegroup.com 2016.4.25
    * description   根据对应的uid获取对应的用户项目信息
    * params    uid
    * return    [projects info]
    */
    public function getProjectsFromUser($params=[])
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $user = O('user', $params['uid']);
        if (!$user->id) $user = O('user', ['gapper_id' => $params['uid']]);
        if (!$user->id) throw new API_Exception(self::$errors[1003], 1003);
        $lab = Q("$user lab")->current();
        $time = Date::time();
        if ($lab->id) {
            $projects = Q("$lab lab_project[dtstart~dtend={$time}]");
            $p = [];
            foreach ($projects as $project) {
                $p[$project->id] = [
                    'id' => $project->id,
                    'name' => H($project->name)
                ];
            }
            return $p;
        }
        return [];
    }


    /*
    * cheng.liu@geneegroup.com 2016.4.25
    * description   根据对应的card_no获取对应的用户信息
    * params    card_no <用户卡号信息>
    * return    [user info]
    */
    public function getUserFromCard($params=[]) 
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $card_no = (string) ($params['card_no'] + 0);
        $card_no_s = (string)(($card_no + 0) & 0xffffff);
        $user = Q("user[card_no={$card_no}|card_no_s={$card_no_s}]:limit(1)")->current();
        if ($user->id) {
            $ret = [
                'id' => (int)$user->id,
                'uname' => H($user->name),
                'lab' => '',
                'time' => Date::time(),
                'card_no' => $params['card_no']
            ];
            if (!$GLOBALS['preload']['people.multi_lab']) {
                $lab = Q("$user lab")->current();
                $ret['lab'] = H($lab->name);
            }
            return $ret;
        }

        $this->_log('[获取用户信息] 接受获取卡号对应人员信息请求 %s => %s(%d)', $card_no, $user->name ?: '--', $user->id);
        return [];
    }

    /*
    * cheng.liu@geneegroup.com 2016.4.25
    * description   将对应的cards更新为该仪器的培训通过用户
    * params    [uuid, cards]
    * return    bool
    */
    public function updateTrainCards($params=[])
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);
        if (!$equipment->require_training) throw new API_Exception(self::$errors[1004], 1004);
        
        $cards = (array)$params['cards'];
        foreach ($cards as $id => $card) {
            $card_no = (string) ($card + 0);
            $card_no_s = (string)(($card_no + 0) & 0xffffff);
            $user = Q("user[card_no={$card_no}|card_no_s={$card_no_s}]:limit(1)")->current();
            if ($user->id == $id) {
                $train = O('ue_training', ['user' => $user, 'equipment' => $equipment]);
                if (!$train->id) {
                    $train->user = $user;
                    $train->equipment = $equipment;
                    $train->ctime = Date::time();
                }
                
                $train->status = UE_Training_Model::STATUS_APPLIED;
                $train->type = $user->member_type;
                $train->atime = 0;
                $train->save();
            }
        }
        $client = O('eq_client', ['equipment' => $equipment]);
        $this->_log('[更新仪器状态信息] 多媒体(%s)更新仪器 %s(%d) 培训人员信息 => %s', $client->mac_addr, $equipment->name, $equipment->id, @json_encode($cards, TRUE));

        return true;
    }

    /*
    * cheng.liu@geneegroup.com 2016.4.25
    * description   更新对应仪器的状态信息
    * params    [uuid, status, description]
    * return    bool
    */
    public function updateEquipmentStatus($params=[]) 
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);
        if (!array_key_exists($params['status'], (array)EQ_Status_Model::$status)) throw new API_Exception(self::$errors[1003], 1003);
        if (!$params['description']) throw new API_Exception(self::$errors[1003], 1003);
        if (!array_key_exists($params['status'], EQ_Status_Model::$status)) throw new API_Exception(self::$errors[1003], 1003);
        $setting_status = H($params['status']);
        if ($setting_status == $equipment->status) return false;
        $now = Date::time();
        if ($setting_status == EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            // ** => 报废
            $status = O('eq_status', [
                    'equipment' => $equipment,
                    'status' => $equipment->status,
                    'dtend' => 0
                    ]);
            if ($status->id) {
                $status->dtend = $now;
                $status = O('eq_status');
            }

            $status->equipment = $equipment;
            $status->dtstart = $now;
            $status->status = $setting_status;
        }
        elseif ($setting_status == EQ_Status_Model::IN_SERVICE) {
            // 其他 => 正常
            $status = O('eq_status', [
                'equipment' => $equipment,
                'status' => $equipment->status,
                'dtend' => 0
            ]);
            if (!$status->id) {
                throw new API_Exception(self::$errors[1004], 1004);
            }
            $status->dtend = $now;
        }
        else {
            // 关闭之前的记录
            foreach(Q("eq_status[equipment=$equipment][dtend=0]") as $s) {
                $s->dtend = $now - 1;
                $s->save();
            }
            // 正常 => 其他
            $status = O('eq_status');
            $status->dtstart = $now;
            $status->equipment = $equipment;
            $status->status = $setting_status;
        }

        if (Config::get('equipment.total_count')) {
            $cache = Cache::factory();
            $equipment_count = $cache->get('equipment_count');
            $equipment_count[$equipment->status] --;
            $equipment_count[$setting_status] ++;
            $cache->set('equipment_count', $equipment_count, 3600);
        }

        $status->description = H($params['description']);
        $status->save();
        $equipment->status = $setting_status;
        $equipment->save();

        $client = O('eq_client', ['equipment' => $equipment]);
        $this->_log('[更新仪器状态信息] 多媒体(%s)更新仪器%s(%d)状态信息为%d', $client->mac_addr, $equipment->name, $equipment->id, $equipment->status);

        return true;
    }

    /*
    * cheng.liu@geneegroup.com 2016.4.25
    * description   更新仪器的反馈信息
    * params    [uuid, feedback]
    * return    bool
    */
    public function updateEquipmentFeedback($params=[])
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);
        $user = O('user', $params['uid']);
        if (!$user->id) $user = O('user', ['gapper_id' => $params['uid']]);
        if (!$user->id) throw new API_Exception(self::$errors[1003], 1003);

        $now = Date::time();

        $record =  Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();

        if ($record->id) {
            // 先进行使用记录的反馈
            $record->feedback = H(T('多媒体反馈信息!'));
            $record->status = H($params['status']);
            //设定samples
            if (!$record->samples_lock && (int)$params['samples']) {
                $record->samples = (int)$params['samples'];
            }
            $record->project = O('lab_project', (int)$params['project']);
            $record->save();
        }
        
        $agent = new Device_Agent($equipment);
        $success = $agent->call('switch_to', ['power_on'=>FALSE]);

        $client = O('eq_client', ['equipment' => $equipment]);

        if ($success) {
            $equipment->is_using = FALSE;
            $equipment->save();
            $this->_log('[提交反馈信息] 多媒体(%s)反馈操作关闭 %s[%d]仪器', $client->mac_addr, $equipment->name, $equipment->id);
        }

        if (!$success) {
            //强制关闭
            $equipment->is_using = FALSE;
            $equipment->save();
            $this->_log('[提交反馈信息] 多媒体(%s)反馈操作强制关闭 %s[%d]仪器', $client->mac_addr, $equipment->name, $equipment->id);

            if ($record->id) {
                $record->dtend = time();
                $record->save();
            }

            $success = TRUE;
        }

        return $success;
    }

    /*
    * cheng.liu@geneegroup.com 2018.4.25
    * description   提供当前使用仪器的特权用户
    * params    [client_id, client_secret, uuid]
    * return    [xxx, xxx]
    */
    public function getFreeAccessCards($params=[])
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);
        $cards = [];
        $free_access_users = $equipment->get_free_access_users();

		foreach ($free_access_users as $user) {
			if ($user->card_no) {
                list($token, $backend) = Auth::parse_token($user->token);
				$cards[(string)$user->card_no] = [
                    'name' => H($user->name),
                    'token' => $token
                ];
			}
		}

        $client = O('eq_client', ['equipment' => $equipment]);
        $this->_log('[获取离线预约卡号] 多媒体(%s) 在 %s抓取了仪器 %s[%d]特权卡号信息', $client->mac_addr, Date::format(), $equipment->name, $equipment->id);
        
        return $cards;
    }

    /*
    * cheng.liu@geneegroup.com 2018.4.25
    * description   提供当前使用仪器的可供离线使用预约用户信息
    * params    [client_id, client_secret, uuid]
    * return    [xxx => [[
        'dtstart' => xx,
        'dtend' => xx
    ]]]
    */
    public function getOfflineReservCards($params=[])
    {
        $this->_ready($params['client_id'], $params['client_secret']);
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);

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
            }
            else {
                foreach ($reservs as $r) {
                    $user = $r->user;
                    $card_no = $user->card_no;
                    if ($card_no) {
                        !is_array($ret['cards'][$card_no]) and $ret['cards'][$card_no] = [];
                        $ret['cards'][$card_no][] = ['dtstart' => $r->dtstart, 'dtend' => $r->dtend];
                        list($token, $backend) = Auth::parse_token($user->token);
                        $ret['users'][$card_no] = [
                            'name' => H($user->name),
                            'token' => $token
                        ];
                    }
                }
            }
        }
        else {
            if ($equipment->require_training) {
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
                            list($token, $backend) = Auth::parse_token($user->token);
                            $ret['users'][$card_no] = [
                                'name' => H($user->name),
                                'token' => $token
                            ];
                        }
                    } 
                }
            }
            else {
                // 没有预约且没有需要培训才能使用的限制情况下，所有人均可以进行随意使用
                $ret['all'][] = ['dtstart' => $dtstart, 'dtend' => $dtend];
            }
        }

        $client = O('eq_client', ['equipment' => $equipment]);
        $this->_log('[获取离线预约卡号] 多媒体(%s) 在 %s抓取了仪器 %s[%d]离线预约卡号信息(%s ~ %s)', $client->mac_addr, Date::format(), $equipment->name, $equipment->id, Date::format($dtstart), Date::format($dtend));

        return $ret;
    }

    /*
    * cheng.liu@geneegroup.com 2018.4.25
    * description   接受三方离线使用数据
    * params    [client_id, client_secret, uuid, record]
    record  = [
        'id' => 1,
        'card' => 'xxxx',
        'token' => 'xxxx',
        'time' => '刷卡时间',
        'status' => 'login / logout / error',
        'extra' / 'feedback' => {
            'status': 1,
            'content' => '',
            'samples' => 1
            'project' => 1
        }
    ]
    * return    ['confirm_record' => ['record_id' =>  1]]
    */
    public function submitOfflineRecords($params=[]) {
        $this->_ready($params['client_id'], $params['client_secret']);
        $equipment = $this->_getEquipment($params['uuid']);
        if (!$equipment->id) throw new API_Exception(self::$errors[1003], 1003);

        $offline_record = $params['record'];
        $ret = [];

        if (count($offline_record)) {
            // 第三代大平板Glogon/Gmeter + Monitor 模式下可能存在需要增加用户名密码输入的可能性；
            // 所以使用兼容我们
            $card_no = (string) $offline_record['card'];
            if ($card_no) {
                $user = Event::trigger('get_user_from_sec_card', $card_no) ? : O('user', ['card_no' => $card_no]);
                if (!$user->id) {
                    $card_no_s = (string)(($card_no + 0) & 0xffffff);
                    $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ? : O('user', ['card_no_s' => $card_no_s]);
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
                $token = Auth::normalize($offline_record['token']);
                $user = O('user', ['token' => $token]);
                if (!$user->id) {
                    $this->_log('[更新离线记录] 登录名%s找不到相应的用户', $token);
                }
            }

            $time = (int) $offline_record['time'];
            switch($offline_record['status']) {
                case 'login':
                    // 清理目前仪器所有因意外未关闭的使用记录
                    foreach (Q("eq_record[dtend=0][dtstart<$time][equipment=$equipment]") as $record) {
                        if ($record->dtstart==$time) {
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
                    $record->is_computer_device = TRUE;
                    $record->dtend = 0;
                    $record->user = $user;

                    if ($record->save()) {
                        Event::trigger('equipments.glogon.offline.login.record_saved', $record);
                    }
                    $equipment->is_using = TRUE;
                    $equipment->save();

                    //刷新对象
                    $client = O('eq_client', ['equipment' => $equipment]);
                    if ($user->id) {
                        $this->_log('[更新离线记录] %s[%d] 在 %s 登入仪器 %s[%d](%s)', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id, $client->mac_addr);
                    }
                    else {
                        $this->_log('[更新离线记录] 未知卡号 %s 在 %s 登入仪器 %s[%d](%s)', $card_no ?: '--', Date::format($time), $equipment->name, $equipment->id, $client->mac_addr);
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
                    $client = O('eq_client', ['equipment' => $equipment]);
                    if ($user->id) {
                        $record =  Q("eq_record[dtstart<$time][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
                        if ($record->id) {
                            $this->_log('[更新离线记录] %s[%d] 在 %s 登出仪器 %s[%d](%s)', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id, $client->mac_addr);
                            $now = Date::time();
                            $record->dtend = min($time, $now);
                            if ($offline_record['extra']) {
                                $feedback = @json_decode($offline_record['extra'], true);
                            }
                            else {
                                $feedback = @json_decode($offline_record['feedback'], TRUE);
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
                                $record->samples = max(Config::get('eq_record.record_default_samples', 1), (int)$feedback['samples']);
                                if ($feedback['project']) $record->project = O('lab_project', $feedback['project']);
                            }
    
                            //负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
                            if ($record->status == EQ_Record_Model::FEEDBACK_NOTHING && $record->user->is_allowed_to('管理使用', $equipment)) {
                                $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                            }
    
                            if ($record->save()) {
                                Event::trigger('equipments.glogon.offline.logout.record_saved', $record);
                            }
    
                        }
                        else {
                            $this->_log('[更新离线记录] %s[%d] 在 %s 登出仪器 %s[%d](%s) 但没找到相应记录', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id, $client->mac_addr);
                        }
                    }
                    else {
                        //离线使用后，恢复网络，关闭使用记录
                        if ($record->id) {
    
                            if ($offline_record['extra']) {
                                $extra = @json_decode($offline_record['extra'], TRUE);
                                $status = $extra['status'];
                                $record->samples = $extra['samples'];
                                $record->feedback = $extra['content'];
                            }
                            else {
                                $status = $offline_record['status'];
                            }
    
                            $record->status = $status;
                            $record->dtend = min($time, $now);
                            if ($record->save()) {
                                Event::trigger('equipments.glogon.offline.logout.record_saved', $record);
                            }
    
                        }
                        $this->_log('[更新离线记录] 未知卡号 %s 在 %s 登出仪器 %s[%d](%s)', $card_no ?: '--', Date::format($time), $equipment->name, $equipment->id, $client->mac_addr);
                    }
                    $equipment->is_using = FALSE;
                    $equipment->save();
                    break;
                case 'error':
                    $this->_log('[更新离线记录] %s[%d] %s 在 %s 尝试 %s 登录失败', $user->name, $user->id, $card_no ?: '--', Date::format($time), $card_no ? '刷卡' : '移动登陆');
                    break;
            }
            $ret['confirm_record'] = ['record_id' => $offline_record['id']];
        }
        return $ret;
    }
}
