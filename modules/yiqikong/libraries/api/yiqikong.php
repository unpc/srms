<?php

class API_YiQiKong
{

    /* 配合 17kong-reserv ( websocket) 请求创建的API机制, 取消所有debade存在的断连机制 */

    public static $errors = [
        1001 => '请求非法!',
        1002 => '您没有权限进行该操作!',
        1010 => '用户信息不合法!',
        2001 => '未找到相应仪器!',
        2002 => '该仪器不接受送样!',
        2003 => '该仪器不接受预约!',
        2004 => '还需填写额外信息, 若无显示请至网页端进行操作!',
        3001 => '您账号余额不足!',
    ];

    public function ping($message)
    {
        return $message ?: 'pong';
    }

    public function authorize($clientId, $clientSecret)
    {
        $yiqikong = Config::get('rpc.servers')['yiqikong'];
        if ($yiqikong['client_id'] == $clientId &&
            $yiqikong['client_secret'] == $clientSecret) {
            $_SESSION['yiqikong.client_id'] = $clientId;
            return session_id();
        }
        return false;
    }

    private static function _checkAuth($clientId, $clientSecret)
    {
        $yiqikong = Config::get('rpc.servers')['yiqikong'];
        if ($yiqikong['client_id'] != $clientId ||
            $yiqikong['client_secret'] != $clientSecret) {
            throw new Error_Exception(self::$errors[1001]);
        }
    }

    private static function _checkValid($user) {
        $default_lab = YiQiKong_Lab::default_lab();
        if (!Q("{$user} $default_lab")->total_count()) {
            $time = time();

            if (($user->dto && $user->dto < $time)
                || ($user->dfrom && $user->dfrom > $time)
                || !$user->atime) {
                throw new Error_Exception(self::$errors[1002]);
            }

        }
    }

    public static function actionAddSample($data)
    {
        $params = json_decode($data, true);
        self::_checkAuth($params['clientId'], $params['clientSecret']);
    	$user = Yiqikong_User::make_user($params);
    	try {
            self::_checkValid($user);
    		if (!$user->id) throw new Error_Exception(self::$errors[1010]);
	    	$equipment = O('equipment', ['yiqikong_id'=> $params['equipment']]);
	    	if (!$equipment->id) throw new Error_Exception(self::$errors[2001]);
            if (!$equipment->accept_sample) throw new Error_Exception(self::$errors[2002]);
            $form = Form::filter([]);
            $form['extra_fields'] = $params['extra'] ? : $params['extra_fields'];
            Extra::validate_extra_value(null, $equipment, 'eq_sample', $form);
            if (!$form->no_error) {
                throw new Error_Exception(self::$errors[2004]);
            }

            Event::trigger('add_sample_validate', $user, $equipment, $params);

            $sample = O('eq_sample', $params[lims_id]);
            if ($sample->id) {
                return self::actionUpdateSample($data);
            }

            Cache::L('ME', $user);
            if (!$user->is_allowed_to('添加送样请求', $equipment) && !$user->is_allowed_to('添加送样记录', $equipment)) {
                throw new Error_Exception(join(',', Lab::messages('sample')));
            }

            $sample         = O('eq_sample');
            $sample->sender = $user;
            if (Q("$user lab")->total_count() == 1) {
                $sample->lab = Q("$user lab")->current();
            }

            $params['samples'] = max($params['samples'], (int)$params['extra']['count']);

            $sample->equipment = $equipment;

            $sample->status          = $params['status'] ?: EQ_Sample_Model::STATUS_APPLIED;
            $sample->dtstart         = $params['start_time'] ?: 0;
            $sample->dtend           = $params['end_time'] ?: 0;
            $sample->dtpickup        = $params['pickup_time'] ?: 0;
            $sample->dtsubmit        = $params['submit_time'] ?: 0;
            $sample->count           = (int) max($params['samples'], 1);
            $sample->success_samples = $params['success_samples'] ?: 0;
            $sample->note            = $params['note'];
            $sample->description     = $params['description'];

            $yiqikong_lab_id = YiQiKong_Lab::default_lab()->id;
            if ( Q("$user lab[id={$yiqikong_lab_id}]")->total_count()) {
            	//判断用户余额是否够送样费用的1.5倍
	            $charge = O('eq_charge');
	            $charge->source = $sample;
	            $lua = new EQ_Charge_LUA($charge);
	            $result = $lua->run(['fee']);
	            $fee = $result['fee'];
	            if ( $params['balance'] < $fee ) {
	            	throw new Error_Exception(self::$errors[3001]);
	            }
            }

            Cache::L('YiQiKongSampleAction', TRUE);

            if ( $sample->save() ) {
                $extra_value = O('extra_value', ['object'=>$sample]);
                if(!$extra_value->id) $extra_value->object = $sample;
                $extra_value->values = $params['extra'];
                $extra_value->save();

                $charge = O('eq_charge', ['source' => $sample]);

                return [
                	'uuid' => $params['uuid'], 
                	'success' => 1, 
                	'params' => [    
                        'method' => 'YiQiKong/Sample/Add',
                        'params' => [
                            'user'            => $sample->sender->gapper_id ?: $sample->sender->email,
                            'equipment'       => $sample->equipment->yiqikong_id,
                            'lims_id'         => $sample->id,
                            'source_site'     => SITE_ID,
                            'source_lab'      => LAB_ID,
                            'samples'         => $sample->count,
                            'success_samples' => $sample->success_samples,
                            'start_time' => $sample->dtstart,
                            'end_time' => $sample->dtend,
                            'pickup_time' => $sample->dtpickup,
                            'submit_time' => $sample->dtsubmit,
                            'status' => $sample->status,
                            'charge' => $charge->amount ?: $fee,
                            'fee' => $charge->auto_amount ?: $fee,
                            'note' => $sample->note,
                            'description' => $sample->description,
                            'source_name' => $params['source_name'],
                            'extra' => $params['extra'],
                            'yiqikong' => Q("$user lab[id={$yiqikong_lab_id}]")->total_count()
                        ],
                    ],
                ];
            }

            return ['uuid' => $params['uuid'], 'error_msg' => I18N::T('yiqikong', '申请送样请求失败!')];

    	}
    	catch (Error_Exception $e) {
    		return ['uuid' => $params['uuid'], 'error_msg' => $e->getMessage()];
    	}
    }

    public static function actionUpdateSample($data)
    {
        $params = json_decode($data, true);
        self::_checkAuth($params['clientId'], $params['clientSecret']);
        $user = Yiqikong_User::make_user($params);
        self::_checkValid($user);

        try {
            if (!$user->id) {
                throw new Error_Exception(self::$errors[1010]);
            }

            $equipment = O('equipment', ['yiqikong_id' => $params['equipment']]);
            if (!$equipment->id) {
                throw new Error_Exception(self::$errors[2001]);
            }

            if (!$equipment->accept_sample) {
                throw new Error_Exception(self::$errors[2002]);
            }

            $form                 = Form::filter([]);
            $form['extra_fields'] = $params['extra'];
            Extra::validate_extra_value(null, $equipment, 'eq_sample', $form);
            if (!$form->no_error) {
                throw new Error_Exception(self::$errors[2004]);
            }

            Event::trigger('update_sample_validate', $user, $equipment, $params);

            $sample = O('eq_sample', $params['lims_id']);
            Cache::L('ME', $user);
            if (!$user->is_allowed_to('修改', $sample)) {
                throw new Error_Exception(self::$errors[1002]);
            }

            $params['samples'] = max($params['samples'], (int)$params['extra']['count']);

            $params['samples'] = max($params['samples'], (int)$params['extra']['count']);

            $check_keys = [
                'samples'         => 'count',
                'success_samples' => 'success_samples',
                'start_time'      => 'dtstart',
                'end_time'        => 'dtend',
                'submit_time'     => 'dtsubmit',
                'pickup_time'     => 'dtpickup',
                'status'          => 'status',
                'note'            => 'note',
                'description'     => 'description',
            ];

            foreach ($check_keys as $k => $v) {
                if (isset($params[$k])) {
                    $sample->$v = $params[$k];
                }
            }

            $yiqikong_lab_id = YiQiKong_Lab::default_lab()->id;
            if (Q("$user lab[id={$yiqikong_lab_id}]")->total_count()) {
                //判断用户余额是否够送样费用的1.5倍
                $charge         = O('eq_charge');
                $charge->source = $sample;
                $lua            = new EQ_Charge_LUA($charge);
                $result         = $lua->run(['fee']);
                $fee            = $result['fee'];

                if ( $params['balance'] < $fee ) {
                    throw new Error_Exception(self::$errors[3001]);
                }
            }

            Cache::L('YiQiKongSampleAction', true);

            if ($sample->save()) {
                $extra_value = O('extra_value', ['object' => $sample]);
                if (!$extra_value->id) {
                    $extra_value->object = $sample;
                }

                $extra_value->values = $params['extra'];
                $extra_value->save();

                $charge = O('eq_charge', ['source' => $sample]);

                return [
                    'uuid' => $params['uuid'], 
                    'success' => 1, 
                    'params' => [    
                        'method' => 'YiQiKong/Sample/Update',
                        'params' => [
                            'user'            => $sample->sender->gapper_id ?: $sample->sender->email,
                            'equipment'       => $sample->equipment->yiqikong_id,
                            'lims_id'         => $sample->id,
                            'source_site'     => SITE_ID,
                            'source_lab'      => LAB_ID,
                            'samples'         => $sample->count,
                            'success_samples' => $sample->success_samples,
                            'start_time' => $sample->dtstart,
                            'end_time' => $sample->dtend,
                            'pickup_time' => $sample->dtpickup,
                            'submit_time' => $sample->dtsubmit,
                            'status' => $sample->status,
                            'charge' => $charge->amount ?: $fee,
                            'fee' => $charge->auto_amount ?: $fee,
                            'note' => $sample->note,
                            'description' => $sample->description,
                            'source_name' => $params['source_name'],
                            'extra' => $params['extra'],
                            'yiqikong' => Q("$user lab[id={$yiqikong_lab_id}]")->total_count()
                        ],
                    ],
                ];
            }
            return ['uuid' => $params['uuid'], 'error_msg' => I18N::T('yiqikong', '修改送样预约失败!')];
        }
        catch(Error_Exception $e) {
            return ['uuid' => $params['uuid'], 'error_msg' => $e->getMessage()];
        }
    }

    public static function actionDeleteSample($data)
    {
        $params = json_decode($data, true);
        self::_checkAuth($params['clientId'], $params['clientSecret']);
        $sample = O('eq_sample', $params['lims_id']);
        $user = Yiqikong_User::make_user($params);
        self::_checkValid($user);
        try {
            if (!$user->id) {
                throw new Error_Exception(self::$errors[1010]);
            }

            if (!$sample->id) {
                throw new Error_Exception(self::$errors[1001]);
            }

            Cache::L('ME', $user);
            if (!$user->is_allowed_to('修改', $sample)) {
                throw new Error_Exception(self::$errors[1002]);
            }

            Cache::L('YiQiKongSampleAction', true);
            return $sample->delete() ? [
                    'success' => 1,
                    'uuid' => $params['uuid'],
                    'params' => $params
                ] : [
                    'success' => 0, 
                    'uuid' => $params['uuid'],
                    'error_msg' => I18N::T('yiqikong', '删除送样预约失败!'
                )];
        }
        catch(Error_Exception $e) {
            return ['uuid' => $params['uuid'], 'error_msg' => $e->getMessage()];
        }
    }

    public static function actionAddComponent($data) {
        $params = json_decode($data, TRUE);
        // 唉，有点蠢
        if (isset($params['extra_fields']['count']) && !isset($params['count'])) {
            $params['count'] = $params['extra_fields']['count'];
        }
        Cache::L('add_component_form', $params);
        self::_checkAuth($params['clientId'], $params['clientSecret']);

        /* 非APP H5 的预约走这里 （LIMS && 小程序）  */
        if (!isset($params['session']) || $params['session'] != 'ctrl-reserve') {
            return API_Calendar::postCommitComponent($data);
        }

        if ($params['lims_id']) {
            $c = O('eq_reserv', $params['lims_id']);
            if ($c->id) {
                return self::actionUpdateComponent($data);
            }
        }

        try {
            $user = Yiqikong_User::make_user($params);
            if (!$user->id) throw new Error_Exception(self::$errors[1010]);

            self::_checkValid($user);

            $equipment = O('equipment', ['yiqikong_id'=> $params['equipment']]);
            if (!$equipment->id) throw new Error_Exception(self::$errors[2001]);
            if (!$equipment->accept_reserv) throw new Error_Exception(self::$errors[2003]);
            $form = Form::filter([]);
            if ($params['extra_fields'] && !$params['extra']) $params['extra'] = $params['extra_fields'];
            $form['extra_fields'] = $params['extra'];
            Extra::validate_extra_value(null, $equipment, 'eq_reserv', $form);
            if (!$form->no_error) {
                throw new Error_Exception(self::$errors[2004]);
            }

            // 目前不确定是否有别的地方用到了$tmp_params['dtstart'],临时转换下类型
            $tmp_params = $params;
            $tmp_params['dtstart'] = strtotime($tmp_params['dtstart']);
            $tmp_params['dtend'] = strtotime($tmp_params['dtend']) - 1;
            Event::trigger('add_component_validate', $user, $equipment, $tmp_params);

            /* 封装虚假预约信息查是否需要计费来进行判断限额问题 */
            $yiqikong_lab_id = YiQiKong_Lab::default_lab()->id;
            if (Q("$user lab[id={$yiqikong_lab_id}]")->total_count()) {
                $reserv            = O('eq_reserv');
                $reserv->user      = $user;
                $reserv->equipment = $equipment;
                if (Q("$user lab")->total_count() == 1) {
                    $reserv->lab = Q("$user lab")->current();
                }
                $reserv->dtstart = strtotime($params['dtstart']);
                $reserv->dtend = strtotime($params['dtend']) - 1;
                //判断用户余额是否够送样费用的1.5倍
                $charge         = O('eq_charge');
                $charge->source = $reserv;
                $lua = new EQ_Charge_LUA($charge);
                $result = $lua->run(['fee']);
                $fee = $result['fee'];
                if ( $params['balance'] < $fee ) {
                    throw new Error_Exception(self::$errors[3001]);
                }
            }

            $calendar = O('calendar', ['parent' => $equipment, 'type' => 'eq_reserv']);
            if (!$calendar->id) {
                $calendar         = O('calendar');
                $calendar->parent = $equipment;
                $calendar->type   = 'eq_reserv';
                $calendar->name   = I18N::T('eq_reserv', '%equipment的预约', ['%equipment' => $equipment->name]);
                $calendar->save();
            }
            $component = O('cal_component');
            $component->organizer = $user;
            $component->calendar = $calendar;
            $component->name = $params['title'];
            $component->description = $params['description'];
            $component->dtstart = strtotime($params['dtstart']);
            $component->dtend = strtotime($params['dtend']) - 1;
            $component->type = $params['type'] ?: Cal_Component_Model::TYPE_VEVENT;
            $component->token = $params['token'];

            Cache::L('ME', $user);
            if (!$user->is_allowed_to('添加', $component)) {
                throw new Error_Exception(join(',', Lab::messages(Lab::MESSAGE_ERROR)));
            }

            Cache::L('YiQiKongReservAction', true);

            if ($component->save()) {
                $reserv = O('eq_reserv', ['component' => $component]);

                if ($reserv->id) {
                    $extra_value = O('extra_value', ['object'=>$reserv]);
                    if(!$extra_value->id) $extra_value->object = $reserv;
                    $extra_value->values = $params['extra'];
                    $extra_value->save();
                    Event::trigger('add_component_submit', $user, $equipment, $params, $reserv);
                }

                $charge = O('eq_charge', ['source' => $reserv]);
                return [
                    'uuid' => $params['uuid'], 
                    'success' => 1, 
                    'params' => [    
                        'method' => 'YiQiKong/Reserve/Add',
                        'params' => [
                            'user'        => $reserv->user->gapper_id ?: $reserv->user->email,
                            'equipment'   => $reserv->equipment->yiqikong_id,
                            'lims_id'     => $reserv->id,
                            'source_site' => SITE_ID,
                            'source_lab'  => LAB_ID,
                            'status'      => $reserv->status,
                            'dtstart'     => Date::format($reserv->dtstart),
                            'dtend'       => Date::format($reserv->dtend),
                            'description' => $component->description,
                            'title'       => $component->name,
                            'type'        => $component->type,
                            'charge'      => $charge->amount,
                            'fee'         => $charge->auto_amount,
                            'extra'       => $params['extra'],
                            'yiqikong'    => Q("$user lab[id={$yiqikong_lab_id}]")->total_count(),
                        ],
                    ],
                ];
            }

            if (L('MERGE_COMPONENT_ID')) {
                $id        = L('MERGE_COMPONENT_ID');
                $component = O('cal_component', $id);
                $reserv    = O('eq_reserv', ['component' => $component]);
                return [
                    'uuid' => $params['uuid'], 
                    'success' => 1, 
                    'params' => [    
                        'method' => 'YiQiKong/Reserve/Add',
                        'params' => [
                            'user'        => $reserv->user->gapper_id ?: $reserv->user->email,
                            'equipment'   => $reserv->equipment->yiqikong_id,
                            'merge'       => true,
                            'lims_id'     => $reserv->id,
                            'source_site' => SITE_ID,
                            'source_lab'  => LAB_ID,
                            'status'      => $reserv->status,
                            'dtstart'     => Date::format($reserv->dtstart),
                            'dtend'       => Date::format($reserv->dtend),
                            'description' => $component->description,
                            'title'       => $component->name,
                            'type'        => $component->type,
                            'charge'      => $charge->amount,
                            'fee'         => $charge->auto_amount,
                            'extra'       => $params['extra'],
                            'yiqikong'    => Q("$user lab[id={$yiqikong_lab_id}]")->total_count(),
                        ],
                    ],
                ];
            }

            return ['uuid' => $params['uuid'], 'error_msg' => Lab::messages(Lab::MESSAGE_ERROR) ?: I18N::T('yiqikong', '申请预约请求失败!')];
        }
        catch(Error_Exception $e) {
            return ['uuid' => $params['uuid'], 'error_msg' => $e->getMessage()];
        }

    }

    public static function actionUpdateComponent($data)
    {
        $params = json_decode($data, true);
        self::_checkAuth($params['clientId'], $params['clientSecret']);

        /* LIMS标示，之后可以用特殊标示符来进行区分 */
        if ($params['call_week_rel']) {
            return self::actionPostCalendarCommit($data);
        }

        try {
            $reserv = O('eq_reserv', $params['lims_id']);
            $component = O('cal_component', $reserv->component->id);
            if (!$component->id) throw new Error_Exception(self::$errors[1010]);
            $user = Yiqikong_User::make_user($params);

            self::_checkValid($user);

            if (!$user->id) throw new Error_Exception(self::$errors[1010]);
            $equipment = O('equipment', ['yiqikong_id'=> $params['equipment']]);
            if (!$equipment->id) throw new Error_Exception(self::$errors[2001]);
            if (!$equipment->accept_reserv) throw new Error_Exception(self::$errors[2003]);
            $form = Form::filter([]);
            if ($params['extra_fields'] && !$params['extra']) $params['extra'] = $params['extra_fields'];
            $form['extra_fields'] = $params['extra'];
            Extra::validate_extra_value(null, $equipment, 'eq_reserv', $form);
            if (!$form->no_error) {
                throw new Error_Exception(self::$errors[2004]);
            }

            Event::trigger('update_component_validate', $user, $equipment, $params);
            /* 封装虚假预约信息查是否需要计费来进行判断限额问题 */
            $yiqikong_lab_id = YiQiKong_Lab::default_lab()->id;
            if (Q("$user lab[id={$yiqikong_lab_id}]")->total_count()) {
                $reserv            = O('eq_reserv');
                $reserv->user      = $user;
                $reserv->equipment = $equipment;
                if (Q("$user lab")->total_count() == 1) {
                    $reserv->lab = Q("$user lab")->current();
                }
                $reserv->dtstart = strtotime($params['dtstart']);
                $reserv->dtend = strtotime($params['dtend']) - 1;
                //判断用户余额是否够送样费用的1.5倍
                $charge         = O('eq_charge');
                $charge->source = $reserv;
                $lua = new EQ_Charge_LUA($charge);
                $result = $lua->run(['fee']);
                $fee = $result['fee'];
                if ( $params['balance'] < $fee ) {
                    throw new Error_Exception(self::$errors[3001]);
                }
            }

            isset($params['user_info']['user_local']) and $user->id
                and $component->organizer = $user;

            isset($params['dtstart'])
                and $component->dtstart = strtotime($params['dtstart']);

            isset($params['dtend'])
                and $component->dtend = strtotime($params['dtend']) - 1;

            isset($params['type'])
                and $component->type = $params['type'];

            isset($params['description'])
                and $component->description = $params['description'];

            isset($params['title'])
                and $component->name = $params['title'];

            isset($params['approval'])
                and $component->approval = $params['approval'];

            isset($params['approval_description'])
                and $component->approval_description = $params['approval_description'];

            $operator = O('user', $params['userId']);
            if (!$operator->is_allowed_to('修改', $component)) {
                $messages = Lab::messages(Lab::MESSAGE_ERROR);
                if (count($messages)) {
                    throw new Error_Exception(join(' ', $messages));
                } else {
                    throw new Error_Exception(self::$errors[1002]);
                }
            }

            Cache::L('YiQiKongReservAction', true);

            if ($component->save()) {
                $reserv = O('eq_reserv', ['component' => $component]);
                $charge = O('eq_charge', ['source' => $reserv]);
                if ($reserv->id) {
                    $extra_value = O('extra_value', ['object'=>$reserv]);
                    if(!$extra_value->id) $extra_value->object = $reserv;
                    $extra_value->values = $params['extra'];
                    $extra_value->save();
                    Event::trigger('update_component_submit', $user, $equipment, $params, $reserv);
                }
                return [
                    'uuid' => $params['uuid'], 
                    'success' => 1, 
                    'params' => [
                        'method' => 'YiQiKong/Reserve/Update',
                        'params' => [
                            'user'        => $reserv->user->gapper_id ?: $reserv->user->email,
                            'equipment'   => $reserv->equipment->yiqikong_id,
                            'lims_id'     => $reserv->id,
                            'source_site' => SITE_ID,
                            'source_lab'  => LAB_ID,
                            'status'      => $reserv->status,
                            'dtstart'     => Date::format($reserv->dtstart),
                            'dtend'       => Date::format($reserv->dtend),
                            'description' => $component->description,
                            'title' => $component->name,
                            'type' => $component->type,
                            'charge' => $charge->amount,
                            'fee' => $charge->auto_amount,
                            'extra' => $params['extra'],
                            'yiqikong' => Q("$user lab[id={$yiqikong_lab_id}]")->total_count()
                        ],
                    ],
                ];
            }
            return ['uuid' => $params['uuid'], 'error_msg' => I18N::T('yiqikong', '修改预约失败!')];
        }
        catch(Error_Exception $e) {
            return ['uuid' => $params['uuid'], 'error_msg' => $e->getMessage()];
        }
    }

    public static function actionDeleteComponent($data)
    {
        $params = json_decode($data, true);
        self::_checkAuth($params['clientId'], $params['clientSecret']);
        try {
            $reserv = O('eq_reserv', $params['lims_id']);
            $component = O('cal_component', $reserv->component->id);
            if (!$component->id) {
                throw new Error_Exception(self::$errors[1001]);
            }

            $reserv = O('eq_reserv', ['component' => $component]);
            if (!$reserv->id) throw new Error_Exception(self::$errors[1001]);
            $user = Yiqikong_User::make_user($params);

            self::_checkValid($user);

            if (!$user->id) {
                throw new Error_Exception(self::$errors[1010]);
            }

            Cache::L('ME', $user);
            //有权限, 并且成功
            if (!$user->is_allowed_to('删除', $component)) {
                throw new Error_Exception(self::$errors[1002]);
            }

            Cache::L('YiQiKongReservAction', true);
            return $component->delete() ? [
                'success' => 1,
                'uuid' => $params['uuid'],
                'params' => $params
            ] : [
                'success' => 0, 
                'uuid' => $params['uuid'],
                'error_msg' => I18N::T('yiqikong', '删除预约失败!')
            ];
        }
        catch(Error_Exception $e) {
            return ['uuid' => $params['uuid'], 'error_msg' => $e->getMessage()];
        }
    }

    public static function actionGetStatus($data)
    {
        $params = json_decode($data, true);
        self::_checkAuth($params['clientId'], $params['clientSecret']);
        try {
            $equipment = O('equipment', ['yiqikong_id' => $params['equipment']]);

            if (!$equipment->id) {
                throw new Error_Exception(self::$errors[2001]);
            }

            $data = [];
            $data['uuid'] = $params['uuid'];
            $data['success'] = 1;

            $data['params'] = [
                'source'     => LAB_ID,
                'equipment'  => $equipment->yiqikong_id,
                'monitoring' => $equipment->is_monitoring || $equipment->connect,
            ];

            // 使用中
            if ($equipment->is_using) {
                $data['params']['using'] = true;
                $user                    = $equipment->current_user();

                $data['params']['current_user'] = [
                    'id'   => $user->gapper_id,
                    'name' => $user->name,
                ];

                //此处不进行翻译
                $data['params']['start_time'] = date('Y/m/d H:i:s', Q("eq_record[equipment={$equipment}][dtend=0]:order(dtstart D):limit(1)")->current()->dtstart);
            }
            // 未使用
            else {
                $data['params']['using'] = false;
            }

            return $data;
        }
        catch(Error_Exception $e) {
            return ['uuid' => $params['uuid'], 'error_msg' => $e->getMessage()];
        }
    }

    public static function actionCheckPermission($data)
    {
        $params = json_decode($data, true);
        self::_checkAuth($params['clientId'], $params['clientSecret']);
        try {
            $equipment = O('equipment', ['yiqikong_id'=> $params['equipment']]);
            $user = Yiqikong_User::make_user($params);

            if (!$user->id || !$equipment->id) {
                throw new Error_Exception;
            }

            $data = [];
            $data['uuid'] = $params['uuid'];
            $data['success'] = 1;

            $data['params'] = [
                'source'    => LAB_ID,
                'equipment' => $equipment->yiqikong_id,
                'user'      => $params['user'],
            ];

            Cache::L('ME', $user);
            // 权限检查判断
            if (!$equipment->is_using) {
                // 仪器未使用, 判断用户是否有权限开机
                if ($user->is_allowed_to('管理使用', $equipment) || !$equipment->cannot_access($user, Date::time())) {
                    $result = true;
                } else {
                    $messages = Lab::messages(Lab::MESSAGE_ERROR);
                    if (!count($messages)) {
                        $messages = ['您无权使用该仪器'];
                    }
                    $result = $messages;
                }
                $data['params']['permission'] = 'switchOn';
            } else {
                // 仪器在使用, 判断用户是否有权限关机
                $now    = Date::time();
                $record = Q("eq_record[dtstart<={$now}][dtend=0][equipment={$equipment}][user={$user}]:sort(dtstart D):limit(1)")->current();

                if ($user->is_allowed_to('管理使用', $equipment) || $record->id) {
                    $result = true;
                } else {
                    $result = ['您无权使用该仪器'];
                }
                $data['params']['permission'] = 'switchOff';
            }

            $data['params']['result'] = $result;
            //$data['params']['result'] = TRUE;
            return $data;
        }
        catch(Error_Exception $e) {
            return ['uuid' => $params['uuid'], 'error_msg' => $e->getMessage()];
        }
    }

    public static function actionSwitch($data)
    {
        $params = json_decode($data, true);
        self::_checkAuth($params['clientId'], $params['clientSecret']);
        try {
            $now = Date::time();
            $equipment = O('equipment', ['yiqikong_id'=> $params['equipment']]);
            $user = Yiqikong_User::make_user($params);

            if (!$user->id || !$equipment->id) {
                throw new Error_Exception;
            }

            Cache::L('YiQiKongSwitchAction', true);

            $data = [];
            $data['uuid'] = $params['uuid'];
            $data['success'] = 1;

            $data['params'] = [
                'source'    => LAB_ID,
                'equipment' => $equipment->yiqikong_id,
                'user'      => $params['user'],
            ];

            Cache::L('ME', $user);
            switch ($params['action']) {
                case 'switchOn':
                    // 用户有权管理, 或者用户可使用
                    // 可开机, 则开机

                    if ($user->is_allowed_to('管理使用', $equipment) || !$equipment->cannot_access($user, Date::time())) {
                        //进行物理开机

                        $agent = new Device_Agent($equipment);
                        // $agent->call 调用有返回值则开机成功, 否则操作失败
                        $data['params']['result'] = $agent->call('switch_to', ['power_on' => true]);
                        $data['params']['action'] = 'switchOn';
                        // 如果是电源控制器的控制方式, 因为不能及时获取开机状态, sleep(1) 后查询仪器状态
                        if (strpos($equipment->control_address, 'gmeter://') == 0) {
                            sleep(1);
                            $eq = O('equipment', $equipment->id);
                            if ($eq->is_using) {
                                $data['params']['result'] = true;
                            }
                        }
                    }
                    break;
                case 'switchOff':
                    if ($user->is_allowed_to('管理使用', $equipment) || $record = Q("eq_record[dtstart<={$now}][dtend=0][equipment={$equipment}][user={$user}]:sort(dtstart D):limit(1)")->current()->id) {
                        //进行物理关机

                        $feedback = $params['feedback'];

                        $agent = new Device_Agent($equipment);

                        $data['params']['result'] = $agent->call('switch_to', [
                            'power_on' => false,
                            'feedback' => json_encode([
                                'feedback' => $feedback['feedback'],
                                'samples'  => $feedback['samples'],
                                'status'   => $feedback['status'],
                                'project'  => $feedback['project'],
                            ]),
                        ]);
                        $data['params']['action'] = 'switchOff';
                        // 如果是电源控制器的控制方式, 因为不能及时获取开机状态, sleep(1) 后查询仪器状态
                        if (strpos($equipment->control_address, 'gmeter://') == 0) {
                            sleep(1);
                            $eq = O('equipment', $equipment->id);
                            if (!$eq->is_using) {
                                $data['params']['result'] = true;
                            }
                        }
                    }
                    break;
                default:
            }
            return $data;
        }
        catch(Error_Exception $e) {
            return ['uuid' => $params['uuid'], 'error_msg' => $e->getMessage()];
        }
    }

    public function get_configs()
    {
        //获取站点配置
        //是否启用use_type
        $enable_use_type = Config::get('equipment.enable_use_type');
        $use_type = [];
        if ($enable_use_type) {
            foreach (EQ_Record_Model::$use_type as $k => $v) {
                $use_type[] = [
                    'value' => $k,
                    'name' => $v,
                ];
            }
        }

        $data['use_type'] = $use_type ?? [];

        $data['support_door'] = Module::is_installed('entrance') ? true : false;
        $data['support_vidcam'] = Module::is_installed('vidmon') ? true : false;

        $data['source_name'] = LAB_ID;

        $data['approval'] = Module::is_installed('approval_flow') ? 'approval_flow' : 'yiqikong_approval';
        $data['sample_config'] = [
            'status' => EQ_Sample_Model::$status,
            'color' => EQ_Sample_Model::$status_background_color
        ];
        return $data;
    }

    public function get_remote_data($key = "", $params = [])
    {
        $result = Event::trigger('equipment.reserv.extra.fields.value', $key, $params);
	    return $result;
    }

    public function get_object_links($user_id = 0, $params = [])
    {
        $user = o('user', $user_id);
        if (!$user->id) return [];

        $object = o($params['object_name'], $params['object_id']);
        if (!$object->id) return [];

        $links = new ArrayIterator;
        Event::trigger("yiqikong.object.links[".$object->name()."]", $user, $object, $params, $links);  
        return (array) $links;
    }

    public function get_remote_approval($params) {
        switch ($params['source_name']) {
            case 'reserve':
                $approval = o('approval', ['source' => o('eq_reserv', $params['source_id'])]);
                if ($approval->id) {
                    Control_Equipment_Approval::on_approval_saved($e, $approval, [], $new_data = ['id' => $approval->id]);
                }
                break;
            case 'sample':
                $approval = o('approval', ['source' => o('eq_sample', $params['source_id'])]);
                if ($approval->id) {
                    Control_Equipment_Approval::on_approval_saved($e, $approval, [], $new_data = ['id' => $approval->id]);
                }
                break;
        }
    }

    public function approval_pre_operation($params) {
        switch ($params['source_name']) {
            case 'reserve':$params['source_name'] = "eq_reserv";break;
            case 'sample':$params['source_name'] = "eq_sample";break;
        }
        $operation = 'reject';
        if ($params['approval'] == 1) {
            $operation = 'pass';
        }
        $approval = O('approval', ['source_name' => $params['source_name'], 'source_id' => $params['source_id']]);
        if ($approval->id) {
            $validate = Event::trigger("approval_not_{$operation}_validate", $approval, $params);
			if ($validate) {
				return ['result' => false, 'message' => join(',', Lab::messages(Lab::MESSAGE_ERROR))];
			} else {
                return ['result' => true, 'message' => '可进行审批'];
            }
        } else {
            return ['result' => false, 'message' => '审批不存在'];
        }
    }
}
