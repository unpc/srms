<?php

class Debade_YiQiKong {

    static $feedback_status_map = [
        1 => EQ_Record_Model::FEEDBACK_NORMAL,
        2 => EQ_Record_Model::FEEDBACK_PROBLEM,
        0 => EQ_Record_Model::FEEDBACK_NOTHING,
    ];

    public static function ready($user_info, $equipment_id) {
        $user = O('user', ['gapper_id'=> $user_info['gapper_id']]);

        if (!$user->id) {
            $user = O('user');
            $user->name = $user_info['username'];
            $user->email = $user_info['email'];
            $user->gapper_id = $user_info['gapper_id'];
            $user->hidden = 1;
            $user->atime = time();
            $user->save();
            $default_lab = YiQiKong_Lab::default_lab($equipment_id);
            $user->connect($lab);
        }

        $billing_depts = Q('billing_department');
        foreach ($billing_depts as $dept) {
            foreach (Q("$user lab") as $lab) {
                $account = O('billing_account', ['lab' => $lab, 'department' => $department]);
                $account->lab = $lab;
                $account->department = $dept;
                $account->balance = '100000000';
                $account->credit_line = '100000000';
                $account->save();
            }
        }

        return $user;
    }
    
    public static function action_status($params) {

        try {
            $equipment = O('equipment', ['yiqikong_id'=> $params['equipment']]);

            if (!$equipment->id) throw new Error_Exception;

            $msg = [];

            $msg['method'] = 'YiQiKong/Control/getStatus';

            $data = [];
            $data['source'] = LAB_ID;

            $data['equipment'] = $params['equipment'];
            $data['monitoring'] = $equipment->is_monitoring;

            // 使用中
            if ($equipment->is_using) {
                $data['using'] = TRUE;
                $user = $equipment->current_user();

                $data['current_user'] = [
                    'id'=> $user->gapper_id,
                    'name'=> $user->name,
                ];

                //此处不进行翻译
                $data['start_time'] = date('Y/m/d H:i:s', Q("eq_record[equipment={$equipment}][dtend=0]:order(dtstart D):limit(1)")->current()->dtstart);
            }
            else {
                $data['using'] = FALSE;
            }

            $msg['params'] = $data;

            //返回结果
            Debade_Queue::of('YiQiKong')->push($msg, 'control');
        }
        catch(Error_Exception $e) {
        }
    }

    public static function action_permission($params) {

        try {
            $equipment = O('equipment', ['yiqikong_id'=> $params['equipment']]);
            $user = O('user', ['gapper_id'=> $params['user']]);

            if (!$user->id || !$equipment->id)  throw new Error_Exception;

            //权限检查判断
            switch($params['permission']) {
                case 'switchOn' :
                    //用户有权管理, 或者用户可使用
                    if ($user->is_allowed_to('管理使用', $equipment) || !$equipment->cannot_access($user, Date::time())) {
                        $result = TRUE;
                    }
                    else {

                        $messages = Lab::messages(Lab::MESSAGE_ERROR);
                        if (!count($messages)) {
                            $messages = ['您无权使用该仪器'];
                        }

                        $result = $messages;
                    }
                    break;
                case 'switchOff' :
                    $now = Date::time();
                    $record = Q("eq_record[dtstart<={$now}][dtend=0][equipment={$equipment}][user={$user}]:sort(dtstart D):limit(1)")->current();

                    if ($user->is_allowed_to('管理使用', $equipment) || $record->id) {
                        $result = TRUE;
                    }
                    else {
                        $result = ['您无权使用该仪器'];
                    }
                    break;
            }

            $msg = [];
            $msg['method'] = 'YiQiKong/Control/checkPermission';

            $data = [];
            $data['source'] = LAB_ID;
            $data['equipment'] = $params['equipment'];
            $data['user'] = $params['user'];
            $data['permission'] = $params['permission'];
            $data['result'] = $result;

            $msg['params'] = $data;

            Debade_Queue::of('YiQiKong')->push($msg, 'control');
        }
        catch(Error_Exception $e) {
        }
    }

    public static function action_switch($params) {

        try {
            $now = Date::time();
            $equipment = O('equipment', ['yiqikong_id'=> $params['equipment']]);
            $user = O('user', ['gapper_id'=> $params['user']]);

            if (!$user->id || !$equipment->id)  throw new Error_Exception;

            Cache::L('ME', $user);

            switch($params['action']) {
                case 'switchOn' :
                    // 用户有权管理, 或者用户可使用
                    // 可开机, 则开机

                    if ($user->is_allowed_to('管理使用', $equipment) || !$equipment->cannot_access($user, Date::time())) {
                        //进行物理开机

                        $agent = new Device_Agent($equipment);
                        $agent->call('switch_to', ['power_on'=> TRUE]);
                    }
                    break;
                case 'switchOff' :

                    if ($user->is_allowed_to('管理使用', $equipment) || $record = Q("eq_record[dtstart<={$now}][dtend=0][equipment={$equipment}][user={$user}]:sort(dtstart D):limit(1)")->current()->id) {
                        //进行物理关机

                        $feedback = $params['feedback'];

                        $agent = new Device_Agent($equipment);
                        $agent->call('switch_to', [
                            'power_on'=> FALSE,
                            'feedback'=> [
                                'feedback'=> $feedback['feedback'],
                                'samples'=> $feedback['samples'],
                                'status'=> $feedback['status'],
                                'project'=> $feedback['project'],
                            ],
                        ]);
                    }
                    break;
                default :
            }
        }
        catch(Error_Exception $e) {
        }
    }
    
    public static function action_record_add($params) {
        //user
        //equipment
        //samples
        //start_time
        //end_time
        //feedback
        //status
        //force

        $user = isset($params['user_info']) ? self::ready($params['user_info'], $params['equipment']) : O('user', ['gapper_id' => $params['user']]);

        try {
            // $user = O('user', ['gapper_id'=> $params['user']]);
            $user = isset($params['user_info']) ? self::ready($params['user_info'], $params['equipment']) : O('user', ['gapper_id' => $params['user']]);

            if (!$user->id) {
                throw new Error_Exception;
            }

            $equipment = O('equipment', ['yiqikong_id'=> $params['equipment']]);

            if (!$equipment->id) {
                throw new Error_Exception;
            }

            $dtstart = $params['start_time'];
            $dtend = $params['end_time'];

            if ($params['force'] && $params['end_time']) {
                Q("eq_record[equipment={$equipment}][dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}|$dtstart=dtstart~dtend]")->delete_all();
            }

            $record = O('eq_record');
            $record->equipment = $equipment;
            $record->dtstart = $dtstart;
            $record->dtend = $dtend;
            $record->user = $user;
            $record->feedback = $params['feedback'];
            $record->status = $params['status'];
            $record->samples = max(1, $params['samples']);

            if ($record->save()) {
                //nothing
            }
            else {

                $err_msg = Lab::messages(Lab::MESSAGE_ERROR);
                if (!count($err_msg)) {
                    $err_msg = ['您无权添加该使用记录'];
                }

                //字符串传递
                $err_msg = join(', ', array_unique($err_msg));

                $msg = [
                    'jsonrpc'=> '2.0',
                    'method'=> 'YiQiKong/Record/Add',
                    'params'=> [
                        'equipment'=> $params['equipment'],
                        'user'=> $params['user'],
                        'start_time'=> $params['start_time'],
                        'end_time'=> $params['end_time'],
                        'err_msg'=> $err_msg,
                    ],
                ];

                DeBaDe_Queue::of('YiQiKong')->push($msg, 'record');
            }
        }
        catch(Error_Exception $e) {
        }
    }

    public static function action_record_update($params) {
        //user
        //equipment
        //samples
        //start_time
        //end_time
        //feedback
        //status
        //force
        //id
        try {
            $user = O('user', ['gapper_id'=> $params['user']]);

            if (!$user->id) {
                throw new Error_Exception;
            }

            $equipment = O('equipment', ['yiqikong_id'=> $params['equipment']]);

            if (!$equipment->id) {
                throw new Error_Exception;
            }

            $id = $params['id'];
            $record = O('eq_record', $id);

            if (!$record->id) {
                throw new Error_Exception;
            }

            $dtstart = $params['start_time'];
            $dtend = $params['end_time'];

            if ($params['force'] && $params['end_time']) {
                Q("eq_record[id!={$id}][equipment={$equipment}][dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}|$dtstart=dtstart~dtend]")->delete_all();
            }

            $check_keys = [
                'samples'=> 'samples',
                'start_time'=> 'dtstart',
                'end_time'=> 'dtend',
                'status'=> 'status',
                'feedback'=> 'feedback',
                'status'=> 'status',
            ];

            foreach($check_keys as $k => $v) {
                if (isset($params[$k])) {
                    switch($k) {
                        case 'samples' :
                            $record->samples = max(Config::get('eq_record.record_default_samples'), $params[$k]);
                            break;
                        default :
                        $record->$v = $params[$k];
                    }
                }
            }

            if ($record->save()) {
                //nothing
            }
            else {

                $err_msg = Lab::messages(Lab::MESSAGE_ERROR);
                if (!count($err_msg)) {
                    $err_msg = ['您无权修改该使用记录'];
                }

                //字符串传递
                $err_msg = join(', ', array_unique($err_msg));

                $msg = [
                    'jsonrpc'=> '2.0',
                    'method'=> 'YiQiKong/Record/Update',
                    'params'=> [
                        'id'=> $params['id'],
                        'err_msg'=> $err_msg,
                    ],
                ];

                DeBaDe_Queue::of('YiQiKong')->push($msg, 'record');
            }
        }
        catch(Error_Exception $e) {
        }
    }

    public static function action_record_delete($params) {

        $id = $params['id'];

        $record = O('eq_record', $id);

        if ($record->delete()) {
            //nothing
        }
        else {
            $err_msg = Lab::messages(Lab::MESSAGE_ERROR);
            if (!count($err_msg)) {
                $err_msg = ['您无权删除该使用记录'];
            }

            //字符串传递
            $err_msg = join(', ', array_unique($err_msg));

            $msg = [
                'jsonrpc'=> '2.0',
                'method'=> 'YiQiKong/Record/Delete',
                'params'=> [
                    'id'=> $params['id'],
                    'err_msg'=> $err_msg,
                ],
            ];

            DeBaDe_Queue::of('YiQiKong')->push($msg, 'record');
        }
    }
}
