<?php
class Equipment_API
{
    static $record_status_label = [
        EQ_Record_Model::FEEDBACK_NOTHING => 'nofeedback',
        EQ_Record_Model::FEEDBACK_PROBLEM => 'problem',
        EQ_Record_Model::FEEDBACK_NORMAL => 'normal',
    ];

    static $record_type_label = [
        EQ_Record_Model::USE_TYPE_USING => 'use',
        EQ_Record_Model::USE_TYPE_TRAINING => 'training',
        EQ_Record_Model::USE_TYPE_TEACHING => 'teaching',
        EQ_Record_Model::USE_TYPE_MAINTENANCE => 'maintain',
        EQ_Record_Model::USE_TYPE_TESTING => 'sample',
        EQ_Record_Model::USE_TYPE_ANALYZING => 'analysis',
    ];

    private static function log()
    {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            $str = vsprintf($format, $args);
            Log::add(strtr('%name %str', [
                '%name' => '[Logon API]',
                '%str' => $str,
            ]), 'devices');
        }
    }

    public static function format($equipment)
    {
        $incharges = Q("{$equipment}<incharge user");
        $current_record = Q("{$equipment} eq_record[dtstart][dtend=0]");
        $ret = [
            'id' => (int) $equipment->id,
            'name' => $equipment->name,
            'deviceIdentifier' => $equipment->control_address,
        ];

        foreach ($incharges as $incharge) {
            $ret['owner'][] = User_API::user_format($incharge);
        }

        foreach ($current_record as $record) {
            $ret['currentLog'] = self::log_format($record);
        }
        return $ret;
    }

    public static function log_format($record)
    {

        $equipment = $record->equipment;
        $icon_url = Config::get('uno.icon_url') ?: Config::get('system.base_url');

        $icon_cache_file = $equipment->icon_file('real');
        if (!$icon_cache_file) {
            $icon_cache_file = Core::file_exists(PRIVATE_BASE . 'icons/image/real.png', '*');
        }

        $ret = [
            'id' => $record->id,
            'user' => User_API::user_format($record->user),
            'startTime' => (int) $record->dtstart,
            'endTime' => (int) $record->dtend ?: null,
            'equipment' => [
                'id' => $record->equipment->id,
                'name' => $record->equipment->name,
                'icon' => [
                    'original' => $icon_url . Cache::cache_file($icon_cache_file) . '?_=' . $equipment->mtime,
                    '32×32' => $icon_url . Cache::cache_file($equipment->icon_file('32')) . '?_=' . $equipment->mtime,
                ],
                'contacts' => Q("{$record->equipment} user.contact")->to_assoc('id', 'name'),
                'phone' => $record->equipment->phone,
                'email' => $record->equipment->email,
                'location' => $record->equipment->location->name,
            ],
            'status' => self::$record_status_label[$record->status],
            'samples' => (int) $record->samples ?: 0,
            'preheat' => (int) $record->preheat ?: null,
            'cooling' => (int) $record->cooling ?: null,
        ];

        if ($record->agent->id) {
            $ret['agent'] = [
                'id' => $record->agent->id,
                'name' => $record->agent->name,
            ];
        }
        if ($record->reserv->id) {
            $ret['reserv'] = [
                'dtstart' => $record->reserv->dtstart,
                'dtend' => $record->reserv->dtend,
                'ctime' => $record->reserv->ctime,
            ];
        }
        $source = Event::trigger('sample_form.charge_get_source', $record) ?: $record;
        $charge = O("eq_charge", ['source' => $source]);
        $amount = $charge->amount;
        // $auto_amount = $charge->auto_amount;
        if (!$charge->id || !$charge->charge_type || $charge->charge_type == 'reserv' || $record->equipment->charge_template['reserv']) {
            $reserv_charge = O('eq_charge', ['source' => $record->reserv]);
        }

        if ($reserv_charge->id) {
            $amount += $reserv_charge->amount;
            // $auto_amount += $reserv_charge->auto_amount;
        }
        $ret['charge_amount'] = sprintf('%.2f', $amount);
        // $ret['charge_auto_amount'] = sprintf('%.2f', $auto_amount);

        switch ($record->status) {
            case EQ_Record_Model::FEEDBACK_NORMAL:
                $ret['status'] = I18N::T('equipments', '正常');
                break;
            case EQ_Record_Model::FEEDBACK_PROBLEM:
                $ret['status'] = I18N::T('equipments', '故障');
                break;
            default:
                $ret['status'] = I18N::T('equipments', '未反馈');
                break;
        }
        $ret['feedback'] = $record->feedback;
        $description = '<p>' . join('</p><p>',  (array) Event::trigger('eq_record.description', $record)) . '</p>';
        $description = preg_replace('/<a[^>]*?>(.*)<\/a>/is', '${1}', $description);
        $ret['description'] = preg_replace('/<script[^>]*>(.*?)<\/script>/', "", $description);

        $extra_value = O('extra_value', ['object' => $record]);
        if ($extra_value->id) {
            $ret = array_merge($ret, Extra_API::extra_value_format($extra_value));
        }
        if (Config::get('equipment.enable_use_type')) {
            $ret['type']= self::$record_type_label[$record->use_type];
        }
        return $ret;
    }

    public static function announce_format($announce)
    {
        $user = L("gapperUser");
        if ($user->id) {
            $read = (bool)Q("{$user}<read {$announce}")->total_count();
        } else {
            $read = false;
        }
        return [
            'id' => (int) $announce->id,
            'title' => $announce->title,
            'content' => $announce->content,
            'author' => User_API::user_format($announce->author),
            'equipment' => [
                'id' => (int) $announce->equipment->id,
                'name' => (int) $announce->equipment->name,
            ],
            'is_sticky' => (bool) $announce->is_sticky,
            'ctime' => (int)$announce->ctime,
            'read' => $read
        ];
    }

    public static function binding_get($e, $params, $data, $query)
    {
        $equipment = O('equipment', ['control_address' => $params[0]]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }
        $e->return_value = self::equipment_format($equipment);
        return;
    }

    public static function binding_post($e, $params, $data, $query)
    {
        $cache = Cache::factory('redis');
        if ($data['authCode']) {
            $equipment_id = $cache->get('equipment_auth_code_' . $data['authCode']);
        } elseif ($data['equipmentId']) {
            $equipment_id = $data['equipmentId'];
        }
        $equipment = O('equipment', ['id' => $equipment_id]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }

        $other_equipment = O('equipment', ['control_address' => $data['deviceIdentifier']]);
        if ($other_equipment->id && $other_equipment->id != $equipment->id) {
            throw new Exception("deviceIdentifier exist ($other_equipment->name[$other_equipment->id])", 400);
        }

        if (preg_match('/^vdi:/', $data['deviceIdentifier'])) {
            $equipment->control_mode = 'agent';
            $cache = Cache::factory('redis');
            $cache->set("equipment_online_" . $equipment->id, true, 30);
        } elseif (preg_match('/^([A-Z0-9]{2}:){5}[A-Z0-9]{2}$/', $data['deviceIdentifier'])) {
            $equipment->control_mode = 'bluetooth';
        }
        $equipment->control_address = $data['deviceIdentifier'];
        $equipment->save();
        $e->return_value = self::format($equipment);
        return;
    }

    public static function binding_patch($e, $params, $data, $query)
    {
        $equipment = O('equipment', ['control_address' => $params[0]]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }
        $cache = Cache::factory('redis');
        // agent 每15s发送一次在线状态
        $cache->set("equipment_online_" . $equipment->id, true, 30);
        $e->return_value = self::format($equipment);
        return;
    }

    public static function bindings_get($e, $params, $data, $query)
    {
        foreach (explode(',', $query['deviceIdentifier$']) as $deviceIdentifier) {
            $equipment = O('equipment', ['control_address' => trim($deviceIdentifier)]);
            if (!$equipment->id) {
                continue;
            }
            $ret['items'][] = self::format($equipment);
        }
        $ret['total'] = count($ret['items']);
        $e->return_value = $ret;
        return;
    }

    public static function bindings_patch($e, $params, $data, $query)
    {
        $cache = Cache::factory('redis');
        $ret = ['deviceIdentifiers' => []];
        foreach ($data['deviceIdentifiers'] as $deviceIdentifier) {
            $equipment = O('equipment', ['control_address' => $deviceIdentifier]);
            if (!$equipment->id) {
                continue;
            }
            $cache->set("equipment_online_" . $equipment->id, true, 10 * 60);
            $ret['deviceIdentifiers'][] = $deviceIdentifier;
        }
        $e->return_value = $ret;
        return;
    }

    public static function binding_delete($e, $params, $data, $query)
    {
        $equipment = O('equipment', ['control_address' => $params[0]]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }

        $cache = Cache::factory('redis');
        $cache->set("equipment_online_" . $equipment->id, false, 1);
        $equipment->control_mode = '';
        $equipment->control_address = '';
        $equipment->save();
        $e->return_value = self::format($equipment);
        return;
    }

    public static function announcement_permission_post($e, $params, $data, $query)
    {
        $me = L('gapperUser');
        $equipment = O('equipment', ['id' => $data['equipmentId']]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }

        if ($GLOBALS['preload']['equipment.enable_announcement']) {
            if ($equipment->status != EQ_STATUS_MODEL::NO_LONGER_IN_SERVICE) {
                if (!$me->is_allowed_to('添加公告', $equipment) && Event::trigger('enable.announcemente', $equipment, $me)) {
                    $e->return_value = [
                        'allowed' => false,
                        'reason' => I18N::T('equipments', '您需阅读过仪器公告，方可使用仪器!')
                    ];
                    return;
                }
            }
        }
        $e->return_value = [
            'allowed' => true,
        ];
        return;
    }

    public static function log_permission_post($e, $params, $data, $query)
    {
        if (isset($params[0])) {
            $me = L('gapperUser');
            $eq_record = O('eq_record', ['id' => $params[0]]);
            if (!$eq_record->id) {
                throw new Exception('log not found', 404);
            }

            $links = [
                'actions' => []
            ];
    
            if ($me->is_allowed_to('修改', $eq_record)) {
                $links['actions'][] = [
                    'title' => I18N::T('equipments', '编辑'),
                    'action' => 'edit',
                ];
            }
            if ($me->is_allowed_to('删除', $eq_record)) {
                $links['actions'][] = [
                    'title' => I18N::T('equipments', '删除'),
                    'action' => 'delete',
                ];
            }
    
            $links['total'] = count($links['actions']);
            $e->return_value = $links;
            return;
        }

        $equipment = O('equipment', ['id' => $data['equipmentId']]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }
        $user = O('user', ['id' => $data['userName']]);
        if (!$user->id) {
            $user = Event::trigger('get_user_by_username', $data['userName']);
        }
        if (!$user->id) {
            $e->return_value = [
                'allowed' => false,
                'reason' => I18N::T('equipments', '找不到对应用户'),
            ];
            return;
        }

        $time = $data['timestamp'] ?: Date::time();
        if (!$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, $time)) {
            self::log('用户%s[%d]无权使用%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);
            $messages = Lab::messages(Lab::MESSAGE_ERROR);
            if (count($messages)) {
                //清空Lab::$messages,得到正确的错误提示
                Lab::$messages[Lab::MESSAGE_ERROR] = [];
                $e->return_value = [
                    'allowed' => false,
                    'reason' => join(' ', array_map(function ($msg) {
                        return I18N::T('equipments', $msg);
                    }, $messages)),
                ];
            } else {
                $e->return_value = [
                    'allowed' => false,
                    'reason' => I18N::T('equipments', '您无权使用%equipment', [
                        '%equipment' => $equipment->name,
                    ]),
                ];
            }
            return;
        }

        self::log('用户%s[%d]可以使用%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);
        $e->return_value = [
            'allowed' => true,
        ];
        return;
    }

    public static function log_post($e, $params, $data, $query)
    {
        $equipment = O('equipment', ['id' => $data['equipmentId']]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }
        $user = O('user', ['id' => $data['userName']]);
        if (!$user->id) {
            $user = Event::trigger('get_user_by_username', $data['userName']);
        }
        if (!$user->id) {
            $user = O('user');
        }
        if (!$data['startTime'] && !$data['endTime']) {
            throw new Exception('wrong startTime and endTime', 400);
        }

        $power_on = $data['endTime'] ? false : true;
        $now = Date::time();
        $startTime = round($data['startTime']);
        $endTime = round($data['endTime']);
        self::log('%s[%d](%s) 尝试记录使用日志%s[%d] (%s) 开始时间: %s, 结束时间: %s', $user->name, $user->id, $data['userName'], $equipment->name, $equipment->id, $equipment->ref_no, $startTime, $endTime);
        $equipment->is_using = $power_on ? 1 : 0;
        $equipment->user_using = $power_on ? $user : null;
        $equipment->save();

        $e->return_value = self::_swith_equipment($power_on, $equipment, $user, $startTime, $endTime);
        return;
    }

    private static function _swith_equipment($power_on, $equipment, $user, $startTime, $endTime)
    {
        if ($power_on) {
            // 关闭该仪器所有因意外未关闭的record
            foreach (Q("eq_record[dtend=0][dtstart<=$startTime][equipment=$equipment]") as $record) {
                // 如果是同一个人的使用记录 上传多次开机, 后面的开机请求不处理(不切分使用记录)
                if ($record->user->id == $user->id) {
                    return;
                }
                if ($record->dtstart == $startTime) {
                    $record->delete();
                    continue;
                }
                $record->dtend = $startTime - 1;
                // 关闭因为意外未关闭的record，应该为未反馈
                // $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                $record->save();
            }

            $record = O('eq_record');
            $record->is_computer_device = true;
            $record->dtstart = $startTime;
            $record->dtend = 0;
            $record->user = $user;
            $record->equipment = $equipment;
            $record->samples = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples');

            if ($record->save()) {
                // Event::trigger('equipments.glogon.switch_to.login.record_saved', $record, $data);
                self::log('%s[%d] 成功记录使用日志[%d]%s[%d] (%s) 开始时间: %s, 结束时间: %s', $user->name, $user->id, $record->id, $equipment->name, $equipment->id, $equipment->ref_no, $startTime, $endTime);
                $ret = ['id' => (int) $record->id];
            } else {
                $ret = ['id' => 0];
            }
        } else {
            $record = Q("eq_record[dtstart<{$endTime}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
            if ($record->id) {
                $record->dtend = $endTime;
                // } else {
                //     $record = O('eq_record');
                //     $record->is_computer_device = true;
                //     $record->dtstart = $startTime;
                //     $record->dtend = $endTime;
                //     $record->user = $user;
                //     $record->equipment = $equipment;
                //     $record->samples = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples');
            }

            if ($record->save()) {
                // Event::trigger('equipments.glogon.switch_to.logout.record_saved', $record, $data);
                self::log('%s[%d] 成功记录使用日志[%d]%s[%d] (%s) 开始时间: %s, 结束时间: %s', $user->name, $user->id, $record->id, $equipment->name, $equipment->id, $equipment->ref_no, $startTime, $endTime);
                $ret = ['id' => (int) $record->id];
            } else {
                $ret = ['id' => 0];
            }
        }
        return $ret;
    }

    public static function feedback_patch($e, $params, $data, $query)
    {
        $record = O('eq_record', $params[0]);
        $equipment = $record->equipment;
        $me = L('gapperUser');
        if (!$record->id) {
            throw new Exception('Not Found', 404);
        }
        $form = [];
        $form['feedback'] = trim($data['feedback']['feedback']);
        $form['record_status'] = array_flip(self::$record_status_label)[$data['feedback']['status']];
        if (Module::is_installed('eq_comment')) {
            $form['service_attitude'] = $data['eqComment1']['serviceAttitude'];
            $form['service_quality'] = $data['eqComment1']['serviceQuality'];
            $form['technical_ability'] = $data['eqComment1']['technicalAbility'];
            $form['emergency_capability'] = $data['eqComment1']['emergencyCapability'];
            $form['detection_performance'] = $data['eqComment2']['detectionPerformance'];
            $form['accuracy'] = $data['eqComment3']['accuracy'];
            $form['compliance'] = $data['eqComment3']['compliance'];
            $form['timeliness'] = $data['eqComment3']['timeliness'];
            $form['sample_processing'] = $data['eqComment3']['sampleProcessing'];
            $form['comment_suggestion'] = $data['commentSuggestion'];
        }
        $form = Form::filter($form);

        if (!$me->is_allowed_to('反馈', $record)) {
            throw new Exception('Forbidden', 403);
        }
        $form->validate('record_status', 'not_empty', I18N::T('equipments', '请选择当前状态!'));
        if ($form['record_status'] == EQ_Record_Model::FEEDBACK_PROBLEM) {
            $form->validate('feedback', 'not_empty', I18N::T('equipments', '请认真填写反馈信息!'));
        }
        Event::trigger('feedback.form.submit', $record, $form);

        if (!$form->no_error) {
            $messages = [];
            foreach ($form->errors as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $messages[] = $vv;
                }
            }
            throw new Exception(join(" ", $messages), 400);
        }

        $record->feedback = $form['feedback'];
        $record->status = $form['record_status'];

        if ($record->save()) {
            Log::add(strtr(
                '[equipments_api] %user_name[%user_id]填写了%equipment_name[%equipment_id]仪器的使用记录[%record_id]反馈',
                [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%equipment_name' => $equipment->name,
                    '%equipment_id' => $equipment->id,
                    '%record_id' => $record->id
                ]
            ), 'journal');
            $e->return_value = self::log_format($record);
        }
    }

    public static function log_user_post($e, $params, $data, $query)
    {
        $equipment = O('equipment', $params[0]);
        $me = L('gapperUser');
        $form = Form::filter([
            'user_name' => $data['userName'],
            'user_email' => $data['userEmail'],
            'user_org' => $data['userOrg'],
            'phone' => $data['userPhone'],
            'tax_no' => $data['taxNo']
        ]);

        if (!$me->is_allowed_to('管理仪器临时用户', $equipment)) {
            throw new Exception('Forbidden', 403);
        }
        $form->validate('user_name', 'not_empty', I18N::T('equipments', '用户姓名 不能为空!'));

        if (!$form['user_email']) {
            $form->set_error('user_email', I18N::T('equipments', '电子邮箱 不能为空!'));
        } else {
            $form->validate('user_email', 'is_email', I18N::T('equipments', '电子邮箱 填写有误!'));
            if (!count($form->errors['user_email'])) {
                //系统中存在已有该user_email的用户了
                if (O('user', ['email' => trim($form['user_email'])])->id) {
                    $form->set_error('user_email', I18N::T('equipments', '电子邮箱 已存在!'));
                }
            }
        }

        $form
            ->validate('phone', 'not_empty', I18N::T('equipments', '联系电话 不能为空!'))
            ->validate('user_org', 'not_empty', I18N::T('equipments', '单位名称 不能为空!'));
        if (Config::get('people.temp_user.tax_no.required', FALSE)) {
            $form->validate('tax_no', 'not_empty', I18N::T('equipments', '税务登记号 不能为空!'));
        }

        $user = O('user');
        $user->name = $form['user_name'];
        $user->organization = $form['user_org'];
        $user->email = $form['user_email'];
        $user->creator = $me;    // 添加用户的添加人
        $user->ref_no = NULL;
        $user->phone = $form['phone'];
        $user->tax_no = $form['tax_no'];

        if (!$form->no_error) {
            $messages = [];
            foreach ($form->errors as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $messages[] = $vv;
                }
            }
            throw new Exception(join(" ", $messages), 400);
        }

        $user->save();
        if (!$user->id) {
            throw new Exception(I18N::T('equipments', '该电子邮箱已经被他人使用!'), 400);
        } else {
            $user->connect(Equipments::default_lab());
            Log::add(strtr('[equipments] %admin_name[%admin_id] 为 %equipment_name[%equipment_id] 添加临时用户 %user_name[%user_id]', ['%admin_name' => $me->name, '%admin_id' => $me->id, '%equipment_name' => $equipment->name, '%equipment_id' => $equipment->id, '%user_name' => $user->name, '%user_id' => $user->id]), 'admin');
        }
        $e->return_value = User_API::user_format($user);
    }

    public static function log_patch($e, $params, $data, $query)
    {
        $record = O('eq_record', $params[0]);
        $equipment = $record->equipment;
        $me = L('gapperUser');
        if (!$record->id) {
            throw new Exception('Not Found', 404);
        }

        if (!$me->is_allowed_to('修改', $record)) {
            throw new Exception('Forbidden', 403);
        }
        $form = self::_makeForm($data, $record);

        if ($form['user_id']) {
            $user = O('user', $form['user_id']);
            if (!$user->id) {
                $form->set_error('', I18N::T('equipments', '请选择有效的用户!'));
            }
        }
        if ($form['agent_id']) {
            $agent = O('user', $form['agent_id']);
            if (!$agent->id) {
                $form->set_error('', I18N::T('equipments', '请选择有效的代开用户!'));
            }
        }

        if ((isset($form['dtstart']) && isset($form['dtend']) && $form['dtstart'] == $form['dtend'])
            || (!$me->access('管理所有内容') && !Event::trigger('equipments.allow_incharge_edit_dtend', $me, $equipment) && $form['dtstart'] && $form['dtend'] && $form['dtstart'] == $form['dtend'])
        ) {
            $form->set_error('dtend', I18N::T('equipments', '结束时间不能与开始时间相同!'));
        }

        //加入工作时间设置后，添加使用记录时需要检查时间是否在设置时间之内
        $w = date('w', $dtstart);
        $weekday_begindate = $dtstart - $w * 86400;
        $weekday_start = mktime(0, 0, 0, date('m', $weekday_begindate), date('d', $weekday_begindate), date('Y', $weekday_begindate));
        $workingtime = Event::trigger('eq_empower.get_workingtime_week', $equipment->id, $weekday_start, $user);
        $dtstart_time = mktime(date('H', $dtstart), date('i', $dtstart), date('s', $dtstart), '01', '01', '1971');
        if (isset($workingtime)) {
            if (array_key_exists($w, (array)$workingtime)) {
                if ($dtstart_time < $workingtime[$w]['starttime'] || $dtstart_time > $workingtime[$w]['endtime']) {
                    $form->set_error('dtstart', I18N::T('equipments', '非工作时间内不允许添加使用记录'));
                }
            }
        }

        $uncontroluser = [];
        if ($empower->uncontroluser) $uncontroluser = array_keys(json_decode($empower->uncontroluser, TRUE));
        if ($empower->uncontrollab) $uncontrollab = array_keys(json_decode($empower->uncontrollab, TRUE));
        if ($empower->uncontrolgroup) $uncontrolgroup = array_keys(json_decode($empower->uncontrolgroup, TRUE));
        if (!isset($uncontrollab)) {
            $uncontrollab = [];
        }
        if (!isset($uncontrolgroup)) {
            $uncontrolgroup = [];
        }
        if (
            !in_array($user->id, (array)$uncontroluser) &&
            !in_array($lab->id, (array)$uncontrollab) &&
            !in_array($lab->group->id, (array)$uncontrolgroup)
        ) {
            $in_rules = true;
        } else {
            $form->set_error('dtstart', I18N::T('equipments', '非工作时间内不允许添加使用记录'));
        }
        Event::trigger('extra.form.validate', $equipment, 'use', $form);

        if (!$form->no_error) {
            $messages = [];
            foreach ($form->errors as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $messages[] = $vv;
                }
            }
            throw new Exception(join(" ", $messages), 400);
        }

        $record->dtstart = $form['dtstart'];
        $record->dtend = $form['dtend'];
        if (isset($form['samples']) && !$record->samples_lock) {
            $record->samples = (int)max($form['samples'], Config::get('eq_record.record_default_samples'));
        }
        if ($user->id && $user->id != $record->user->id) {
            if (!$record->agent->id) $record->agent = $record->user;
            $record->user = $user;
        }
        if ($form['agent_id'] && $me->is_allowed_to('修改代开者', $record)) {
            $agent = O('user', $form['agent_id']);
            if ($agent->id) {
                $record->agent = $agent;
            }
        }
        if (!$record->user->id) $record->user = $me;

        if (Config::get('equipment.enable_use_type')) {
            $record->use_type = $form['use_type'];
            // $record->use_type_desc = $form['use_type_desc'];
        }
        Event::trigger('eq_record.edit_submit', $record, $form);
        //自定义使用表单存储供lua计算
        if (Module::is_installed('extra')) {
            $record->extra_fields = $form['extra_fields'];
        }
        Event::trigger('extra.form.post_submit', $record, $form);

        if ($record->save()) {
            Log::add(strtr(
                '[equipments_api] %user_name[%user_id]编辑了%equipment_name[%equipment_id]仪器的使用记录[%record_id]',
                [
                    '%user_name' => $me->name,
                    '%user_id' => $me->id,
                    '%equipment_name' => $equipment->name,
                    '%equipment_id' => $equipment->id,
                    '%record_id' => $record->id
                ]
            ), 'journal');
            $e->return_value = self::log_format($record);
        }
    }

    public static function log_delete($e, $params, $data, $query)
    {
        $record = O('eq_record', $params[0]);
        $me = L('gapperUser');
        if (!$record->id || !$me->is_allowed_to('删除', $record)) {
            throw new Exception('Forbidden', 403);
        }

        Log::add(strtr(
            '[equipments_api] %user_name[%user_id]删除%equipment_name[%equipment_id]仪器的使用记录[%record_id]',
            [
                '%user_name' => $me->name,
                '%user_id' => $me->id,
                '%equipment_name' => $record->equipment->name,
                '%equipment_id' => $record->equipment->id,
                '%record_id' => $record->id
            ]
        ), 'journal');

        $record_attachments_dir_path = NFS::get_path($record, '', 'attachments', TRUE);

        $ret = self::log_format($record);
        if ($record->delete()) {
            File::rmdir($record_attachments_dir_path);
        }
        $e->return_value = $ret;
    }

    public static function current_log_get($e, $params, $data, $query)
    {
        $equipment = O('equipment', $params[0]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }

        $current_record = Q("{$equipment} eq_record[dtstart][dtend=0]");
        if (!$current_record->total_count()) {
            throw new Exception('log not found', 404);
        }
        foreach ($current_record as $record) {
            $ret = self::log_format($record);
        }

        $e->return_value = $ret;
        return;
    }

    public static function equipments_get($e, $params, $data, $query)
    {
        $selector = "equipment[status!=2]";
        $pre_selectors = [];

        if ($query['id']) {
            $id = intval($query['id']);
            $selector .= "[id={$id}]";
        }
        if ($query['ref_no']) {
            $ref_no = Q::quote($query['ref_no']);
            $selector .= "[ref_no*=$ref_no]";
        }
        if ($query['status']) {
            $status = intval($query['status']);
            $selector .= "[status={$status}]";
        }
        if ($query['keyWords']) {
            $keywords = Q::quote($query['keyWords']);
            $selector .= "[name*={$keywords}]";
        }
        if ($query['location']) {
            $keywords = Q::quote($query['location']);
            if(Config::get('equipment.location_type_select')){
                $pre_selectors['location'] = "tag_location[name*={$keywords}]";
            }else{
                $pre_selectors['location'] = "tag_location[name*={$keywords}].location";
            }
        }

        if ($query['groupId']) {
            $group = O('tag_group', intval($query['groupId']));
            if ($group->id) {
                $pre_selectors['group'] = "{$group}";
            }
        }

        if ($query['catId']) {
            $cat = O('tag_equipment', intval($query['catId']));
            if ($cat->id) {
                $pre_selectors['cat'] = "{$cat}";
            }
        }

        if ($query['follower']) {
        }

        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(', ', $pre_selectors) . ') ' . $selector;
        }

        $total = Q($selector)->total_count();

        //分页
        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page})";

        $equipments = Q($selector);

        $items = [];
        foreach ($equipments as $equipment) {
            $items[] = self::equipment_format($equipment);
        }

        $ret = [
            'total' => $total,
            'st' => $start,
            'pp' => $per_page,
            'items' => $items,
        ];

        $e->return_value = $ret;
        return;
    }

    public static function equipment_get($e, $params, $data, $query)
    {
        $equipment = O('equipment', $params[0]);

        if (!$equipment->id) {
            $yiqikongid = explode(":",$params[0])[1];
            $equipment = O('equipment', ['yiqikong_id' => $yiqikongid]);
        }

        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }

        $e->return_value = self::equipment_format($equipment);
    }

    public static function equipment_stat_get($e, $params, $data, $query)
    {
        $equipment = O('equipment', $params[0]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }
        $now = Date::time();
        $count = Q("eq_record[equipment={$equipment}][dtstart<=$now]")->total_count();
        $ret = [
            'id' => (int) $equipment->id,
            'refNo' => (string) $equipment->ref_no,
            'useCount' => $count
        ];
        $e->return_value = $ret;
    }

    public static function equipment_format($equipment)
    {
        $owner = Q("{$equipment} user.incharge")->to_assoc('id', 'name');
        $contacts = Q("{$equipment} user.contact")->to_assoc('id', 'name');
        $contacts_name = $contacts ? implode(',', $contacts) : '';
        //仪器分类
        //$root = Tag_Model::root('equipment');
        //$equipment_tags = Q("$equipment tag_equipment[root=$root]")->to_assoc('id', 'name');
        //$equipment_tags_name = $equipment_tags ? implode(',', $equipment_tags) : '';

        $current_record = Q("{$equipment} eq_record[dtstart][dtend=0]:sort(id DESC)")->current();

        $tag_location = $equipment->location;
        $locations = $tag_location->id ? [$tag_location->id => $tag_location->name] : [];
        while ($tag_location->parent->id && $tag_location->parent->root->id) {
            $locations += [$tag_location->parent->id => $tag_location->parent->name];
            $tag_location = $tag_location->parent;
        }

        $tag = $equipment->group;
        $groups = $tag->id ? [$tag->id => $tag->name] : [];
        while ($tag->parent->id && $tag->parent->root->id) {
            $groups += [$tag->parent->id => $tag->parent->name];
            $tag = $tag->parent;
        }

        $icon_url = Config::get('uno.icon_url') ?: Config::get('system.base_url');

        $icon_cache_file = $equipment->icon_file('real');
        if (!$icon_cache_file) {
            $icon_cache_file = Core::file_exists(PRIVATE_BASE . 'icons/image/real.png', '*');
        }

        $data = [
            'id' => (int) $equipment->id,
            'name' => (string) $equipment->name,
            'deviceIdentifier' => (string) $equipment->control_address,
            'owner' => $owner,
            'icon' => [
                'original' => $icon_url . Cache::cache_file($icon_cache_file) . '?_=' . $equipment->mtime,
                '32×32' => $icon_url . Cache::cache_file($equipment->icon_file('32')) . '?_=' . $equipment->mtime,
            ],
            'url' => (string) $equipment->url(),
            'locations' => $locations,
            'acceptSample' => (bool) $equipment->accept_sample,
            'acceptReserv' => (bool) $equipment->accept_reserv,
            'requireTraining' => (bool) $equipment->require_training,
            'reservApproval' =>  (bool) $equipment->need_approval,
            'reservNotify' => (bool)$equipment->reserv_require_pc,
            'sampleNotify' => (bool)$equipment->sample_require_pc,
            'price' => (float) $equipment->price,
            'refNo' => (string) $equipment->ref_no,
            'catNo' => (string) $equipment->cat_no,
            'model' => (string) $equipment->model_no,
            'using' => (bool) $equipment->is_using,
            'specification' => (string) $equipment->specification,
            'specs' => (string) $equipment->tech_specs,
            'features' => (string) $equipment->features,
            'accessories' => (string) $equipment->configs,
            'reservSettings' => (string) $equipment->open_reserv,
            'chargeSettings' => (string) $equipment->charge_info,
            'manufacturingCountry' => (string) $equipment->manu_at,
            'manufacturer' => (string) $equipment->manufacturer,
            'contact' => [
                'name' => (string) $contacts_name,
                'email' => (string) $equipment->email,
                'phone' => (string) $equipment->phone,
            ],
            'status' => (int) $equipment->status,
            'groups' => $groups,
            'controlMode' => (string) $equipment->control_mode,
            'remoteControl' => (bool) (in_array($equipment->control_mode, ['bluetooth', 'computer', 'power']) || !$equipment->control_mode),
            'purchasedDate' => $equipment->purchased_date ? (string) Date::format($equipment->purchased_date, 'Y-m-d') : '',
            'manuDate' => $equipment->manu_date ? (string) Date::format($equipment->manu_date, 'Y-m-d') : '',
            'atime' => $equipment->atime ? (string) Date::format($equipment->atime, 'Y-m-d') : '',
            //'equipmentTags' => (string) $equipment_tags_name,
        ];
        if ($current_record->id) {
            $data['currentLog'] = self::log_format($current_record);
        }

        return $data;
    }

    public static function equipment_filters_get($e, $params, $data, $query)
    {
        $tree = $items = [];
        $tag_groups = Q("tag_group");
        foreach ($tag_groups as $key => $group) {
            $g = [];
            $g['label'] = $group->name;
            $g['value'] = (int) $group->id;
            $g['parent_id'] = $group->parent_id;
            $items[$group->id] = $g;
        }
        foreach ($items as $item) {
            if (isset($items[$item['parent_id']])) {
                $items[$item['parent_id']]['children'][] = &$items[$item['value']];
            } else {
                $tree[] = &$items[$item['value']];
            }
        }
        $groups = self::fit_tags($tree);

        $tree = $items = [];
        $tag_equipments = Q("tag_equipment");
        foreach ($tag_equipments as $key => $group) {
            $g = [];
            $g['label'] = $group->name;
            $g['value'] = (int) $group->id;
            $g['parent_id'] = $group->parent_id;
            $items[$group->id] = $g;
        }
        foreach ($items as $item) {
            if (isset($items[$item['parent_id']])) {
                $items[$item['parent_id']]['children'][] = &$items[$item['value']];
            } else {
                $tree[] = &$items[$item['value']];
            }
        }
        $cats = self::fit_tags($tree);

        $e->return_value = [
            ['name' => '组织机构', 'key' => 'groupId', 'type' => 'number', 'enum' => $groups[0]['children']],
            ['name' => '仪器分类', 'key' => 'catId', 'type' => 'number', 'enum' => $cats[0]['children']],
        ];

        $tag_groups = Q("tag_group");

        return;
    }

    private static function fit_tags(array $tags)
    {
        foreach ($tags as &$tag) {
            unset($tag['parent_id']);
            $tags = $tags;
            if (isset($tag['children'])) {
                $tag['children'] = self::fit_tags($tag['children']);
            }
        }
        return $tags;
    }

    public static function follow_equipments_get($e, $params, $data, $query)
    {
        $user = L("gapperUser");
        if (!$user->id) {
            throw new Exception('user not found', 404);
        }

        $follows = $user->followings('equipment');

        $items = [];
        foreach ($follows as $follow) {
            $items[] = self::equipment_format($follow->object);
        }

        $ret = [
            'total' => count($items),
            'items' => $items,
        ];

        $e->return_value = $ret;
        return;
    }

    public static function logs_get($e, $params, $data, $query)
    {
        $selector = "eq_record";
        $user = L('gapperUser');

        if (isset($query['equipmentId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['equipmentId$']));
            if (!count($ids)) {
                throw new Exception('equipmentId cannot be empty', 404);
            }

            // 查询固定仪器的记录，任意一台没有权限,就应该让他只能看到自己的
            foreach (Q("equipment[id=" . join(",", $ids) . "]") as $equipment) {
                if (!$user->is_allowed_to('列表仪器使用记录', $equipment)) {
                    $selector = "{$user} eq_record";
                    break;
                }
            }

            $selector .= "[equipment_id=" . join(",", $ids) . "]";

        } else {
            // 查询所有的仪器
            if (!$user->is_allowed_to('列表仪器使用记录', 'equipment')) {
                if (!isset($query['userId$']) && !isset($query['labId$'])) {
                    $selector = "{$user} eq_record";
                }
            }
        }

        if (isset($query['userId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['userId$']));
            if (!count($ids)) {
                throw new Exception('userId cannot be empty', 404);
            }
            $selector .= "[user_id=" . join(",", $ids) . "]";
        }
        if (isset($query['labId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['labId$']));
            if (!count($ids)) {
                throw new Exception('labId cannot be empty', 404);
            }
            $selector = "lab#" . join(",", $ids) . " user " . $selector;
        }
        if (isset($query['status'])) {
            $selector .= "[status=" . array_flip(self::$record_status_label)[$query['status']] . "]";
        }
        $selector_times = [];
        if (isset($query['startTime']) && intval($query['startTime'])) {
            $dtstart = intval($query['startTime']);
            $selector_times[] = "dtstart>{$dtstart}";
        }
        if (isset($query['endTime']) && intval($query['endTime'])) {
            $dtend = intval($query['endTime']);
            $selector_times[] = "dtend<{$dtend}";
        }
        if ($dtstart && $dtend) {
            $selector_times[] = "dtstart={$dtstart}~{$dtend}";
        }
        if (count($selector_times)) $selector .= "[" . implode("|", $selector_times) . "]";
        $total = $pp = Q("$selector")->total_count();

        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(dtstart D)";

        $logs = [];
        foreach (Q("$selector") as $log) {
            $logs[] = self::log_format($log);
        }
        $e->return_value = ["total" => $total, "items" => $logs];
    }

    public static function equipment_announces_get($e, $params, $data, $query)
    {
        $selector = "eq_announce";
        if (isset($query['equipmentId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['equipmentId$']));
            if (!count($ids)) {
                throw new Exception('equipmentId Cannot be empty', 404);
            }
            $selector .= "[equipment_id=" . join(",", $ids) . "]";
        }

        $total = $pp = Q("$selector")->total_count();
        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(is_sticky D, mtime D)";

        $announces = [];
        foreach (Q("$selector") as $announce) {
            $announces[] = self::announce_format($announce);
        }
        $e->return_value = ["total" => $total, "items" => $announces];
    }

    public static function equipment_announces_patch($e, $params, $data, $query)
    {
        $user = L("gapperUser");
        $announce = O('eq_announce', $params[0]);
        if (!$announce->id) {
            throw new Exception('announcement not found', 404);
        }
        if (!$user->connected_with($announce, 'read')) {
            $user->connect($announce, 'read');
            Event::trigger('user.eq_announce.connect', $user, $announce);
            Log::add(strtr(
                '[equipments API] %user_name[%user_id]阅读了%equipment_name[%equipment_id]仪器上公告%announce_title[%announce_id]',
                [
                    '%user_name' => $user->name,
                    '%user_id' => $user->id,
                    '%equipment_name' => $announce->equipment->name,
                    '%equipment_id' => $announce->equipment->id,
                    '%announce_title' => $announce->title,
                    '%announce_id' => $announce->id
                ]
            ), 'journal');
        }

        $e->return_value = self::announce_format($announce);
    }

    public static function equipment_accept_patch($e, $params, $data, $query)
    {
        $me = L("gapperUser");
        $equipment = O('equipment', $params[0]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }
        if (isset($data['reserv'])) {
            if (!$me->is_allowed_to('修改预约设置', $equipment)) {
                throw new Exception('Forbbiden', 403);
            } else {
                $equipment->accept_reserv = (int)$data['reserv'];
            }
        }
        if ($equipment->accept_reserv) {
            if (isset($data['reservApproval'])) {
                $equipment->need_approval = (int)$data['reservApproval'];
            }
            if (isset($data['reservNotify'])) {
                $equipment->reserv_require_pc = (int)$data['reservNotify'];
            }
        }

        if (isset($data['sample'])) {
            if (!$me->is_allowed_to('修改送样设置', $equipment)) {
                throw new Exception('Forbbiden', 403);
            } else {
                $equipment->accept_sample = (int)$data['sample'];
            }
        }
        if ($equipment->accept_sample) {
            if (isset($data['sampleNotify'])) {
                $equipment->sample_require_pc = (int)$data['sampleNotify'];
            }
        }
        if (isset($data['training'])) {
            if (!$me->is_allowed_to('修改使用设置', $equipment)) {
                throw new Exception('Forbbiden', 403);
            } else {
                $equipment->require_training = (int)$data['training'];
            }
        }

        $equipment->save();
        $e->return_value = [
            'reserv' => (bool)$equipment->accept_reserv,
            'reservApproval' => (bool)$equipment->need_approval,
            'reservNotify' => (bool)$equipment->reserv_require_pc,
            'sample' => (bool)$equipment->accept_sample,
            'sampleNotify' => (bool)$equipment->sample_require_pc,
            'training' => (bool)$equipment->require_training
        ];
    }

    public static function equipment_permission_post($e, $params, $data, $query)
    {
        $user = L("gapperUser");
        $equipment = O('equipment', $params[0]);
        $e->return_value = [
            'admin' => $user->is_allowed_to('管理使用', $equipment)
        ];
    }

    public static function equipment_state_patch($e, $params, $data, $query)
    {
        $user = L("gapperUser");
        $equipment = O('equipment', $params[0]);
        $is_admin = $user->is_allowed_to('修改仪器使用记录', $equipment);
        $now = time();
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }
        if ($equipment->status != EQ_Status_Model::IN_SERVICE) {
            throw new Exception(I18N::T('equipments', '该设备暂时故障, 不支持远程开关, 如果有其他问题, 请联系管理员.'), 403);
        }

        if (
            $equipment->control_mode == 'computer'
            || ($equipment->control_mode == 'power' && preg_match('/^gmeter/', $equipment->control_address))
        ) {
            $client = new \GuzzleHttp\Client([
                'base_uri' => $equipment->server,
                'http_errors' => FALSE,
                'timeout' => Config::get('device.computer.timeout', 5)
            ]);
        }

        if ($data['power'] === false) {

            if (!$is_admin && Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}][user={$user}]")->total_count() == 0) {
                throw new Exception(I18N::T('equipments', '该设备正在被其他人使用, 您无权关闭. 如果有其他问题,请联系管理员.'), 403);
            }

            if ($client) {
                $success = false;
                $config = Config::get('rpc.client.jarvis');
                try {
                    $success = (bool) $client->post('switch_to', [
                        'headers' => [
                            'HTTP-CLIENTID' => $config['client_id'],
                            'HTTP-CLIENTSECRET' => $config['client_secret'],
                        ],
                        'form_params' => [
                            'uuid' => str_replace('gmeter://', '', $equipment->control_address),
                            'user' => [
                                'equipmentId' => $equipment->id,
                                'username' => $user->token,
                                'cardno' => $user->card_no,
                                'name' => $user->name,
                                'id' => $user->id
                            ],
                            'power_on' => FALSE
                        ]
                    ])->getBody()->getContents();
                } catch (Exception $e) {
                }
            } else {
                $success = (bool)self::_swith_equipment(false, $equipment, $user, $now, $now)['id'];
            }

            if ($success) {
                if ($equipment->control_mode != 'power') {
                    $equipment->is_using = FALSE;
                }
                $equipment->save();
                Log::add(strtr(
                    '[equipments API] %user_name[%user_id]关闭%equipment_name[%equipment_id]仪器',
                    [
                        '%user_name' => $user->name,
                        '%user_id' => $user->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id' => $equipment->id
                    ]
                ), 'journal');
            }
        } elseif ($data['power'] === true) {

            if (!$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, $now)) {
                JS::alert(I18N::T('equipments', '您无权打开该设备电源.\n如果有其他问题,请联系管理员.'));
                return;
            }

            Log::add(strtr('[equipments API] %user_name[%user_id]尝试通过CGI打开%equipment_name[%equipment_id]仪器', [
                '%user_name'      => $user->name,
                '%user_id'        => $user->id,
                '%equipment_name' => $equipment->name,
                '%equipment_id'   => $equipment->id,
            ]), 'journal');

            if ($client) {
                $config  = Config::get('rpc.client.jarvis');
                $success = (bool) $client->post('switch_to', [
                    'headers'     => [
                        'HTTP-CLIENTID'     => $config['client_id'],
                        'HTTP-CLIENTSECRET' => $config['client_secret'],
                    ],
                    'form_params' => [
                        'uuid' => str_replace('gmeter://', '', $equipment->control_address),
                        'user' => [
                            'equipmentId' => $equipment->id,
                            'username' => $user->token,
                            'cardno' => $user->card_no,
                            'name' => $user->name
                        ],
                        'power_on' => true,
                    ],
                ])->getBody()->getContents();
            } else {
                $success = (bool)self::_swith_equipment(true, $equipment, $user, $now, 0)['id'];
            }

            if ($success) {
                if ($equipment->control_mode != 'power') {
                    $equipment->is_using = TRUE;
                }
                $equipment->save();
                Log::add(strtr(
                    '[equipments API] %user_name[%user_id]通过CGI打开%equipment_name[%equipment_id]仪器',
                    [
                        '%user_name' => $user->name,
                        '%user_id' => $user->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id' => $equipment->id
                    ]
                ), 'journal');
                Event::trigger('remote_open_equipment', $user, $equipment);
            }
        }

        $e->return_value = ['power' => ($success ? $data['power'] : (bool)$equipment->is_using)];
    }

    private static function _makeForm($data, $record)
    {
        $form = [];
        $equipment = $record->equipment;
        $extra = O('extra', ['object' => $equipment, 'type' => 'use']);
        if ($extra->id) {
            $categories = $extra->get_categories();
            foreach ($categories as $ck => $category) {
                $data_ck = "category_{$ck}";
                if (!isset($data[$data_ck])) continue;
                $fields = $extra->get_fields($category);
                foreach ($fields as $fk => $field) {
                    $data_fk = "field_{$fk}";
                    if ($field['adopted']) {
                        $form[$fk] = $data[$data_ck][$fk];
                        continue;
                    }
                    switch ($field["type"]) {
                        case Extra_Model::TYPE_RADIO: // 单选
                        case Extra_Model::TYPE_SELECT: // 下拉菜单
                            $form['extra_fields'][$fk] = $field["params"][(int)$data[$data_ck][$data_fk]];
                            break;
                        case Extra_Model::TYPE_CHECKBOX: // 多选
                            if (!$data[$data_ck][$data_fk]) {
                                continue;
                            }
                            $value = array_flip($field["params"]);
                            foreach ($value as $k => $v) {
                                $value[$k] = in_array($v, $data[$data_ck][$data_fk]) ? "on" : null;
                            }
                            $form['extra_fields'][$fk] = $value;
                            break;
                        case Extra_Model::TYPE_TEXT: // 单行文本
                        case Extra_Model::TYPE_NUMBER: // 数值
                        case Extra_Model::TYPE_TEXTAREA: // 多行文本
                            $form['extra_fields'][$fk] = $data[$data_ck][$data_fk];
                            break;
                        case Extra_Model::TYPE_RANGE: // 数值范围
                            $value = [];
                            if (preg_match('/.*_pre/', $data_fk)) {
                                continue;
                            }
                            $form['extra_fields'][$fk] = [
                                $data[$data_ck][$data_fk . "_pre"],
                                $data[$data_ck][$data_fk]
                            ];
                            break;
                        case Extra_Model::TYPE_STAR: // 评星
                            break;
                        case Extra_Model::TYPE_DATETIME: // 日期时间
                            $form['extra_fields'][$fk] = intval($data[$data_ck][$data_fk]);
                            break;
                    }
                }
            }
        }
        // 为了extra.form.validate 和extra.form.post_submit, 硬转数据结构
        $form['equipment_id'] = $equipment->id;
        $form['user_id'] = $data['userId'] ?? $record->user->id;
        $form['user_option'] = $data['user_option'] ?? '';
        $form['agent_id'] = $data['agentId'] ?? $record->agent->id;
        $form['power_on_preheating'] = ($data['preheat'] ?? $record->preheat) ? 'on' : null;
        $form['shutdown_cooling'] = ($data['cooling'] ?? $record->cooling) ? 'on' : null;
        $form['extra_fields'] = $form['extra_fields'] ?? $record->extra_fields;
        $form['samples'] = $data['samples'] ?? $record->samples;
        $form['dtstart'] = $data['dtstart'] ?? $record->dtstart;
        $form['dtend'] = $data['dtend'] ?? $record->dtend;
        $form['use_type'] = array_flip(self::$record_type_label)[$data['type']] ?? $record->use_type;
        if ($data['amount']) {
            $form['record_amount'] = $data['amount'];
            $form['record_custom_charge'] = 'on';
        }
        if (isset($data['chargeDesc'])) {
            $form['charge_desc'] = $data['chargeDesc'];
        }
        return Form::filter($form);
    }
}
