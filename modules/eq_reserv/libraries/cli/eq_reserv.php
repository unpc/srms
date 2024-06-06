<?php

class CLI_EQ_Reserv
{
    // 迟到
    public static function late_reserv($user, $equipment, $reserv)
    {
        if (Module::is_installed('eq_ban')) {
            $user_v = O('user_violation', ['user'=>$user]);
            if (!$user_v->id) {
                $user_v = O('user_violation');
                $user_v->user = $user;
            }
            $user_v->eq_late_count++;
            $user_v->save();
        }

        Log::add(sprintf("%s[%d] eq_late_count => %d", $user->name, $user->id, $user_v->eq_late_count), "miss_check");
        if (!Module::is_installed('eq_ban')) {
            return;
        }

        $root_id = $user->group->root->id;
        $group = $user->group;
        while (true) {
            $max_allowed_late_times = Lab::get('equipment.max_allowed_late_times', Config::get('equipment.max_allowed_late_times'), $group->name, true);
            $group = $group->parent;

            if ($max_allowed_late_times > 0 || $root_id == $group->id) {
                break;
            }
        }
        if (!$max_allowed_late_times) {
            $max_allowed_late_times = Lab::get('equipment.max_allowed_late_times', Config::get('equipment.max_allowed_late_times'), 0);
        }

        if ($max_allowed_late_times > 0 && $user_v->eq_late_count >= $max_allowed_late_times) {
            //[BUG]13525（3）黑名单，加入黑名单时如果因为用户和课题组重复加入一个人时，是不会被覆盖的
            $banned = O('eq_banned', ['user'=>$user,
                // 'lab_id'=>0,
                'object_id'=>0]);
            $banned->user = $user;
            $banned->reason = I18N::T('eq_reserv', '使用设备迟到次数超过系统预定义上限!');
            $banned->atime = 0;
            $banned->save();
            Eq_Ban_Message::add($banned);
            Event::trigger('banned.for.late', $banned);
        }


        $max_eq_allowed_late_times = Lab::get('eq.max_allowed_late_times', Config::get('eq.max_allowed_late_times'), $equipment->group->name, true);
        if ($max_eq_allowed_late_times > 0 && $user_v->eq_late_count >= $max_eq_allowed_late_times) {
            if (!$GLOBALS['preload']['people.multi_lab']) {
                $lab = Q("$user lab")->current();
            }

            $filter = ['user' => $user, 'object' => $equipment->group];
            if ($lab->id) {
                $filter['lab'] = $lab;
            } else {
                $filter['lab_id'] = 0;
            }

            $eq_banned = O('eq_banned', $filter);

            if ($lab->id) {
                $eq_banned->lab = $lab;
                $reason_pre = $lab->name . ': ';
            }
            $eq_banned->user = $user;
            $eq_banned->object = $equipment->group;
            $eq_banned->reason = I18N::T('eq_ban', '使用设备迟到次数超过系统预定义上限!');
            $eq_banned->atime = 0;
            $eq_banned->save();
            Eq_Ban_Message::add($eq_banned);
        }

        Event::trigger('trigger_scoring_rule', $user, 'late',$equipment, $reserv);

        self::_check_eq_allowed_total_count_times($equipment, $user_v);
    }

    public static function late_and_overtime_reserv($user, $equipment, $reserv)
    {
        self::late_reserv($user, $equipment, $reserv);
        self::overtime_reserv($user, $equipment, $reserv);
    }

    // 早退
    public static function leave_early_reserv($user, $equipment, $record, $reserv)
    {
        if ($record->clear_leave_early) {
            return false;
        }

        if (Module::is_installed('eq_ban')) {
            $user_v = O('user_violation', ['user'=>$user]);
            if (!$user_v->id) {
                $user_v = O('user_violation');
                $user_v->user = $user;
            }
            $user_v->eq_leave_early_count ++;
            $user_v->save();
        }

        $incharges = Q("{$equipment} user.incharge");
        foreach ($incharges as $incharge) {
            Notification::send('eq_reserv.leave_early', $incharge, [
                   '%user' => Markup::encode_Q($user),
                   '%equipment' => Markup::encode_Q($equipment),
                   '%contact' => Markup::encode_Q($incharge),
                   '%times' => $user_v->eq_leave_early_count
               ]);
        }


        Notification::send('eq_reserv.leave_early.self', $user, [
                               '%user' => Markup::encode_Q($user),
                               '%equipment' => Markup::encode_Q($equipment),
                               '%times' => $user_v->eq_leave_early_count
                           ]);


        Log::add(sprintf("%s[%d] eq_leave_early_count => %d", $user->name, $user->id, $user_v->eq_leave_early_count), "miss_check");

        if (!Module::is_installed('eq_ban')) {
            return;
        }
        $group = $user->group;
        $root_id = $group->root->id;
        while ($root_id != $group->id) {
            $max_allowed_leave_early_times = Lab::get('equipment.max_allowed_leave_early_times', Config::get('equipment.max_allowed_leave_early_times'), $group->name, true);
            $group = $group->parent;

            if ($max_allowed_leave_early_times > 0) {
                break;
            }
        }
        if (!$max_allowed_leave_early_times) {
            $max_allowed_leave_early_times = Lab::get('equipment.max_allowed_leave_early_times', Config::get('equipment.max_allowed_leave_early_times'), 0);
        }

        if ($max_allowed_leave_early_times > 0 && $user_v->eq_leave_early_count >= $max_allowed_leave_early_times) {
            $banned = O('eq_banned', ['user'=>$user,
                // 'lab_id'=>0,
                'object_id'=>0]);
            $banned->user = $user;
            $banned->reason = I18N::T('eq_reserv', '使用设备早退次数超过系统预定义上限!');
            $banned->atime = 0;
            $banned->save();
            Eq_Ban_Message::add($banned);
        }


        $max_eq_allowed_leave_early_times = Lab::get('eq.max_allowed_leave_early_times', Config::get('eq.max_allowed_leave_early_times'), $equipment->group->name, true);

        if ($max_eq_allowed_leave_early_times > 0 && $user_v->eq_leave_early_count >= $max_eq_allowed_leave_early_times) {
            if (!$GLOBALS['preload']['people.multi_lab']) {
                $lab = Q("$user lab")->current();
            }

            $filter = ['user' => $user, 'object' => $equipment->group];
            if ($lab->id) {
                $filter['lab'] = $lab;
            } else {
                $filter['lab_id'] = 0;
            }

            $eq_banned = O('eq_banned', $filter);

            if ($lab->id) {
                $eq_banned->lab = $lab;
                $reason_pre = $lab->name . ': ';
            }
            $eq_banned->user = $user;
            $eq_banned->object = $equipment->group;
            $eq_banned->reason = I18N::T('eq_ban', '使用设备早退次数超过系统预定义上限!');
            $eq_banned->atime = 0;
            $eq_banned->save();
        }
        Event::trigger('trigger_scoring_rule', $user, 'early',$equipment, $reserv);
        self::_check_eq_allowed_total_count_times($equipment, $user_v);
    }

    public static function late_leave_early_reserv($user, $equipment, $record, $reserv)
    {
        self::late_reserv($user, $equipment, $reserv);
        self::leave_early_reserv($user, $equipment, $record, $reserv);
    }

    // 爽约
    public static function miss_reserv($user, $equipment, $reserv)
    {
        if (Module::is_installed('eq_ban')) {
            $user_v = O('user_violation', ['user'=>$user]);
            if (!$user_v->id) {
                $user_v = O('user_violation');
                $user_v->user = $user;
            }
            $user_v->eq_miss_count ++;
            $user_v->save();
        }

        $incharges = Q("{$equipment} user.incharge");
        foreach ($incharges as $incharge) {
            Notification::send('eq_reserv.misstime', $incharge, [
                   '%user' => Markup::encode_Q($user),
                   '%equipment' => Markup::encode_Q($equipment),
                   '%contact' => Markup::encode_Q($incharge),
                   '%times' => $user_v->eq_miss_count
               ]);
        }


        Notification::send('eq_reserv.misstime.self', $user, [
                               '%user' => Markup::encode_Q($user),
                               '%equipment' => Markup::encode_Q($equipment),
                               '%times' => $user_v->eq_miss_count
                           ]);

        Log::add(sprintf("%s[%d] eq_miss_count => %d", $user->name, $user->id, $user_v->eq_miss_count), "miss_check");

        if (!Module::is_installed('eq_ban')) {
            return;
        }
        $group = $user->group;
        $root_id = $group->root->id;
        while ($root_id != $group->id) {
            $max_allowed_miss_times = Lab::get('equipment.max_allowed_miss_times', Config::get('equipment.max_allowed_miss_times'), $group->name, true);
            $group = $group->parent;
            if ($max_allowed_miss_times > 0 || $max_allowed_miss_times === 0) {
                break;
            }
        }
        if ($max_allowed_miss_times === null) {
            $max_allowed_miss_times = Lab::get('equipment.max_allowed_miss_times', Config::get('equipment.max_allowed_miss_times'), 0);
        }

        if ($max_allowed_miss_times > 0 && $user_v->eq_miss_count >= $max_allowed_miss_times) {
            $banned = O('eq_banned', ['user'=>$user,
                // 'lab_id'=>0,
                'object_id'=>0]);
            $banned->user = $user;
            $banned->lab = Q("{$user} lab")->current();
            $banned->reason = I18N::T('eq_reserv', '使用设备爽约次数超过系统预定义上限!');
            $banned->atime = 0;
            $banned->save();
            Eq_Ban_Message::add($banned);
            Event::trigger('banned.for.miss', $banned);
        }

        $max_eq_allowed_miss_times = Lab::get('eq.max_allowed_miss_times', Config::get('eq.max_allowed_miss_times'), $equipment->group->name, true);

        if ($max_eq_allowed_miss_times > 0 && $user_v->eq_miss_count >= $max_eq_allowed_miss_times) {
            if (!$GLOBALS['preload']['people.multi_lab']) {
                $lab = Q("$user lab")->current();
            }

            $filter = ['user' => $user, 'object' => $equipment->group];
            if ($lab->id) {
                $filter['lab'] = $lab;
            } else {
                $filter['lab_id'] = 0;
            }

            $eq_banned = O('eq_banned', $filter);

            if ($lab->id) {
                $eq_banned->lab = $lab;
                $reason_pre = $lab->name . ': ';
            }
            $eq_banned->user = $user;
            $eq_banned->object = $equipment->group;
            $eq_banned->reason = I18N::T('eq_ban', '使用设备爽约次数超过系统预定义上限!');
            $eq_banned->atime = 0;
            $eq_banned->save();
            Eq_Ban_Message::add($eq_banned);
        }

        Event::trigger('trigger_scoring_rule', $user, 'miss',$equipment, $reserv);

        self::_check_eq_allowed_total_count_times($equipment, $user_v);
    }

    // 超时
    public static function overtime_reserv($user, $equipment, $reserv)
    {
        if (Module::is_installed('eq_ban')) {
            $user_v = O('user_violation', ['user'=>$user]);
            if (!$user_v->id) {
                $user_v = O('user_violation');
                $user_v->user = $user;
            }
            $user_v->eq_overtime_count ++;
            $user_v->save();
        }

        $incharges = Q("{$equipment} user.incharge");
        foreach ($incharges as $incharge) {
            Notification::send('eq_reserv.overtime', $incharge, [
                   '%user' => Markup::encode_Q($user),
                   '%equipment' => Markup::encode_Q($equipment),
                   '%contact' => Markup::encode_Q($incharge),
                   '%times' => $user_v->eq_overtime_count
               ]);
        }


        Notification::send('eq_reserv.overtime.self', $user, [
                               '%user' => Markup::encode_Q($user),
                               '%equipment' => Markup::encode_Q($equipment),
                               '%times' => $user_v->eq_overtime_count
                           ]);


        Log::add(sprintf("%s[%d] eq_overtime_count => %d", $user->name, $user->id, $user_v->eq_overtime_count), "miss_check");

        if (!Module::is_installed('eq_ban')) {
            return;
        }
        $root_id = $user->group->root->id;
        $group = $user->group;
        while (true) {
            $max_allowed_overtime_times = Lab::get('equipment.max_allowed_overtime_times', Config::get('equipment.max_allowed_overtime_times'), $group->name, true);
            $group = $group->parent;

            if ($max_allowed_overtime_times > 0 || $root_id == $group->id) {
                break;
            }
        }
        if (!$max_allowed_overtime_times) {
            $max_allowed_overtime_times = Lab::get('equipment.max_allowed_overtime_times', Config::get('equipment.max_allowed_overtime_times'), 0);
        }

        if ($max_allowed_overtime_times > 0 && $user_v->eq_overtime_count >= $max_allowed_overtime_times) {
            $banned = O('eq_banned', ['user'=>$user,
                // 'lab_id'=>0,
                'object_id'=>0]);
            $banned->user = $user;
            $banned->reason = I18N::T('eq_reserv', '使用设备超时次数超过系统预定义上限!');
            $banned->atime = 0;
            $banned->save();
            Eq_Ban_Message::add($banned);
        }

        $max_eq_allowed_overtime_times = Lab::get('eq.max_allowed_overtime_times', Config::get('eq.max_allowed_overtime_times'), $equipment->group->name, true);

        if ($max_eq_allowed_overtime_times > 0 && $user_v->eq_overtime_count >= $max_eq_allowed_overtime_times) {
            if (!$GLOBALS['preload']['people.multi_lab']) {
                $lab = Q("$user lab")->current();
            }

            $filter = ['user' => $user, 'object' => $equipment->group];
            if ($lab->id) {
                $filter['lab'] = $lab;
            } else {
                $filter['lab_id'] = 0;
            }

            $eq_banned = O('eq_banned', $filter);

            if ($lab->id) {
                $eq_banned->lab = $lab;
                $reason_pre = $lab->name . ': ';
            }
            $eq_banned->user = $user;
            $eq_banned->object = $equipment->group;
            $eq_banned->reason = I18N::T('eq_ban', '使用设备超时次数超过系统预定义上限!');
            $eq_banned->atime = 0;
            $eq_banned->save();
            Eq_Ban_Message::add($eq_banned);
        }
        Event::trigger('trigger_scoring_rule', $user, 'timeout',$equipment, $reserv);

        self::_check_eq_allowed_total_count_times($equipment, $user_v);
    }

    /**
     * 同miss_check方法，但不处理违规行为。只做状态标记
     */
    public static function status_judge(){
        //实际为当前时间后一天
        $now = Date::time() - 24 * 3600;
        Lab::set('last_miss_check_time', $now);
        $dtstart = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));
        $dtstart -= 24 * 3600;
        //搜素当前时间之前的预约
        $status = EQ_Reserv_Model::PENDING;
        $reservs = Q("eq_reserv[status={$status}][dtend<{$now}][dtend>={$dtstart}]");
        error_log('status_judge正在查找:'.date('Y-m-d H:i:s',$dtstart).'---'.date('Y-m-d H:i:s',$now).'的预约');

        foreach ($reservs as $reserv) {
            $user = $reserv->user;
            $equipment = $reserv->equipment;

            $latest_record = Q("eq_record[reserv={$reserv}][dtend>0]:limit(1)")->current();

            //获取状态
            $status = $reserv->get_status(true, $latest_record);

            $ban_status_settings = $equipment->ban_status_settings ?? array_keys(EQ_Reserv_Model::$ban_status_settings);

            error_log('当前仪器允许的状态:'.implode(',',$ban_status_settings));
            error_log('预约原本状态:'.EQ_Reserv_Model::$reserv_status[$status]);

            if(in_array($status,[
                EQ_Reserv_Model::MISSED,
                EQ_Reserv_Model::LEAVE_EARLY,
                EQ_Reserv_Model::OVERTIME,
                EQ_Reserv_Model::LATE,
            ]) && !in_array($status,$ban_status_settings)){
                //仪器如果不记录该违规状态，则默认正常
                $status = EQ_Reserv_Model::NORMAL;
            }
            if($status == EQ_Reserv_Model::LATE_LEAVE_EARLY){
                if(isset($ban_status_settings[EQ_Reserv_Model::LATE]) && !isset($ban_status_settings[EQ_Reserv_Model::LEAVE_EARLY])){
                    $status = EQ_Reserv_Model::LATE;
                }
                if(!isset($ban_status_settings[EQ_Reserv_Model::LATE]) && isset($ban_status_settings[EQ_Reserv_Model::LEAVE_EARLY])){
                    $status = EQ_Reserv_Model::LEAVE_EARLY;
                }
                if(!isset($ban_status_settings[EQ_Reserv_Model::LATE]) && !isset($ban_status_settings[EQ_Reserv_Model::LEAVE_EARLY])){
                    $status = EQ_Reserv_Model::NORMAL;
                }
            }
            if($status == EQ_Reserv_Model::LATE_OVERTIME){
                if(isset($ban_status_settings[EQ_Reserv_Model::LATE]) && !isset($ban_status_settings[EQ_Reserv_Model::OVERTIME])){
                    $status = EQ_Reserv_Model::LATE;
                }
                if(!isset($ban_status_settings[EQ_Reserv_Model::LATE]) && isset($ban_status_settings[EQ_Reserv_Model::OVERTIME])){
                    $status = EQ_Reserv_Model::OVERTIME;
                }
                if(!isset($ban_status_settings[EQ_Reserv_Model::LATE]) && !isset($ban_status_settings[EQ_Reserv_Model::OVERTIME])){
                    $status = EQ_Reserv_Model::NORMAL;
                }
            }
            
            error_log('预约现在状态:'.EQ_Reserv_Model::$reserv_status[$status]);

            $reserv->status = $status;
            $reserv->has_ban = false;//是否已经处理过了
            $reserv->last_status_judge_time = time();
            $reserv->save();
        }
    }

    /**
     * 把标记过状态且标记状态之后24小时的前一天的记录进行违规处理
     */
    public static function ban_record(){
        //实际为当前时间后一天
        $now = Date::time() - 25 * 3600;
        Lab::set('last_miss_check_time', $now);
        $dtstart = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));
        $dtstart -= 24 * 3600;
        $except_status = implode(',',[EQ_Reserv_Model::NORMAL,EQ_Reserv_Model::PENDING]);
        $time = time() - 24 * 3600;//24小时之前
        // $time = time();//24小时之前
        error_log(date('Y-m-d H:i:s',$time));
        $reservs = Q("eq_reserv[status!={$except_status}][dtend<{$now}][dtend>={$dtstart}]");
        error_log('ban_record正在查找:'.date('Y-m-d H:i:s',$dtstart).'---'.date('Y-m-d H:i:s',$now).'的预约');
        foreach($reservs as $reserv){
            
            if(!$reserv->last_status_judge_time || $reserv->last_status_judge_time > $time || $reserv->has_ban) continue;

            error_log('正在处理预约ID:'.$reserv->id);
            error_log('预约结束时间:'.date('Y-m-d H:i:s',$reserv->dtend));

            $status = $reserv->status;
            error_log('状态:'.EQ_Reserv_Model::$reserv_status[$status]);
            $user = $reserv->user;
            $equipment = $reserv->equipment;
            $latest_record = Q("eq_record[reserv={$reserv}][dtend>0]:limit(1)")->current();
            switch ($status) {
                case EQ_Reserv_Model::MISSED:
                    self::miss_reserv($user, $equipment, $reserv);
                    break;
                case EQ_Reserv_Model::LEAVE_EARLY:
                    self::leave_early_reserv($user, $equipment, $latest_record, $reserv);
                    break;
                case EQ_Reserv_Model::LATE_LEAVE_EARLY:
                    self::late_leave_early_reserv($user, $equipment, $latest_record, $reserv);
                    break;
                case EQ_Reserv_Model::OVERTIME:
                    self::overtime_reserv($user, $equipment, $reserv);
                    break;
                case EQ_Reserv_Model::LATE:
                    self::late_reserv($user, $equipment, $reserv);
                    break;
                case EQ_Reserv_Model::LATE_OVERTIME:
                    self::late_and_overtime_reserv($user, $equipment, $reserv);
                    break;
                case EQ_Reserv_Model::NORMAL:
                    Event::trigger('trigger_scoring_rule', $user, 'reserv', $equipment, $reserv);
                    break;
                default:
                    break;
            }
            $reserv->has_ban = true;
            $reserv->save();

            if (!Module::is_installed('credit')) {
                self::violation_exceed_preset($user);
            }
        }
    }

    public static function miss_check()
    {
        die('3.27后升级，执行status_judge及ban_record');
        //实际为当前时间后一天
        $now = Date::time() - 24 * 3600;
        Lab::set('last_miss_check_time', $now);
        $dtstart = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));
        $dtstart -= 24 * 3600;
        //搜素当前时间之前的预约
        $status = EQ_Reserv_Model::PENDING;
        $reservs = Q("eq_reserv[status={$status}][dtend<{$now}][dtend>={$dtstart}]");

        foreach ($reservs as $reserv) {
            $user = $reserv->user;
            $equipment = $reserv->equipment;

            $latest_record = Q("eq_record[reserv={$reserv}][dtend>0]:limit(1)")->current();

            //获取状态
            $status = $reserv->get_status(true, $latest_record);
            $ban_status_settings = $equipment->ban_status_settings ?? EQ_Reserv_Model::$ban_status_settings;
            if(in_array($status,[
                EQ_Reserv_Model::MISSED,
                EQ_Reserv_Model::LEAVE_EARLY,
                EQ_Reserv_Model::OVERTIME,
                EQ_Reserv_Model::LATE,
            ]) && !array_key_exists($status,$ban_status_settings)){
                continue;
            }
            
            switch ($status) {
                case EQ_Reserv_Model::MISSED:
                    self::miss_reserv($user, $equipment, $reserv);
                    break;
                case EQ_Reserv_Model::LEAVE_EARLY:
                    self::leave_early_reserv($user, $equipment, $latest_record, $reserv);
                    break;
                case EQ_Reserv_Model::LATE_LEAVE_EARLY:
                    if(array_key_exists(EQ_Reserv_Model::LATE,$ban_status_settings)){
                        self::late_reserv($user, $equipment, $reserv);
                    }
                    if(array_key_exists(EQ_Reserv_Model::LEAVE_EARLY,$ban_status_settings)){
                        self::leave_early_reserv($user, $equipment, $latest_record, $reserv);
                    }
                    break;
                case EQ_Reserv_Model::OVERTIME:
                    self::overtime_reserv($user, $equipment, $reserv);
                    break;
                case EQ_Reserv_Model::LATE:
                    self::late_reserv($user, $equipment, $reserv);
                    break;
                case EQ_Reserv_Model::LATE_OVERTIME:
                    if(array_key_exists(EQ_Reserv_Model::LATE,$ban_status_settings)){
                        self::late_reserv($user, $equipment, $reserv);
                    }
                    if(array_key_exists(EQ_Reserv_Model::OVERTIME,$ban_status_settings)){
                        self::overtime_reserv($user, $equipment, $reserv);
                    }
                    break;
                case EQ_Reserv_Model::NORMAL:
                    Event::trigger('trigger_scoring_rule', $user, 'reserv', $equipment, $reserv);
                    break;
                default:
                    break;
            }

            $reserv->status = $status;
            $reserv->save();
        }
        /*
         * 2020年03月24日xian.zhou
         */
        if (!Module::is_installed('credit')) {
            $users = Q("eq_reserv[dtend<{$now}][dtend>={$dtstart}] user");
            foreach ($users as $user) {
                self::violation_exceed_preset($user);
            }
        }
    }

    public static function clear_miss_over_count()
    {
        if (!Module::is_installed('eq_ban')) {
            return;
        }
        foreach (Q('user_violation') as $user) {
            $user->eq_miss_count = 0;
            $user->eq_leave_early_count = 0;
            $user->eq_overtime_count = 0;
            $user->eq_late_count = 0;
            printf("用户%s[%d]预约超时和爽约次数请空\n", $user->name, $user->id);
            $user->save();
        }
    }

    //自动创建爽约的eq_reserv对应的record
    public static function auto_create_record()
    {
        //所以非报废仪器
        $except_status = EQ_Status_Model::NO_LONGER_IN_SERVICE;
        $now = Date::time();
        $start = Lab::get('last_auto_create_record_time', 0);
        $last_miss_check_time = Lab::get('last_miss_check_time', 0);

        // miss_check 和 auto_create_record 执行有一定间隔，导致开始时间正好在这段间隔中间的预约不会生成爽约记录，故做此修正
        if ($start && $last_miss_check_time) {
            $start -= ($now - $last_miss_check_time);
        }

        try {
            foreach (Q("equipment[status!={$except_status}]") as $equipment) {
                // 兰州大学需要未控制仪器且未设置自动生成记录的，生成当前用户使用记录.
                Event::trigger('auto_create_record.render', $equipment);
                //当仪器设定可自动创建爽约记录对应的预约记录后再进行后续逻辑
                if ($equipment->auto_create_record) {
                    //查询所有爽约记录
                    // $status = EQ_Reserv_Model::MISSED;
                    foreach (Q("eq_reserv[equipment={$equipment}][dtend={$start}~{$now}]") as $reserv) {
                        self::_try_create_record($reserv);
                    }
                }
            }
        } catch (Exception $e) {
        }

        Lab::set('last_auto_create_record_time', $now);
    }

    // 向glogon推送一下当前的预约
    public static function glogon_current_reserv($id = 0, $equipment_id = 0)
    {
        if (!$id && !$equipment_id) {
            return;
        }

        if (!$id && $equipment_id) {
            $now = Date::time();
            $equipment = O('equipment', $equipment_id);
            $reserv = Q("eq_reserv[equipment=${equipment}][dtstart~dtend={$now}]:limit(1)")->current();
        } else {
            $reserv = O('eq_reserv', $id);
        }

        if ($reserv->equipment->control_mode == 'veronica') {
            // 新glogon
            Event::trigger('eq_reserv.push_current_veronica', $reserv);
        } elseif ($reserv->equipment->control_mode == 'computer') {
            //  旧glogon
            Event::trigger('eq_reserv.push_current_glogon', $reserv);
        } else {
            // 只发是电脑控制的预约
            return;
        }
    }

    private static function _try_create_record($reserv)
    {
        if ($reserv->component->type == Cal_Component_Model::TYPE_VFREEBUSY) return;

        //预约记录有使用记录关联，则不自动生成
        $exist = Q("eq_record[reserv={$reserv}]")->total_count();
        if($exist->id) return;

        $record = O('eq_record');
        $equipment = $reserv->equipment;
        $record->equipment = $equipment;
        $record->user = $reserv->user;
        $record->dtstart = $dtstart = $reserv->dtstart;
        $record->dtend = $dtend = $reserv->dtend - 1;
        $record->reserv = $reserv;
        $record->is_missed = $reserv->status == EQ_Reserv_Model::MISSED ? true : false; // 爽约记录标记
        $record->samples = (int) $reserv->count ? : 0; // 送样数取预约送样数
        // 使用中的使用记录
        if (Q("eq_record[equipment={$equipment}][dtend=0][dtstart<$dtstart]")->total_count()
        // 包含该dtstart, dtend跨度的使用记录
        || Q("eq_record[equipment={$equipment}][dtstart=$dtstart~$dtend|dtend=$dtstart~$dtend|dtstart~dtend=$dtstart]")->total_count()
        ) {
            //有使用中的使用记录
            //不进行使用记录创建，发送邮件
            foreach (Q("{$equipment} user.incharge") as $incharge) {
                $subject = I18N::HT('eq_reserv', '提醒: 在您负责的[%equipment]下, 系统添加一条使用记录失败!', [
                    '%equipment'=> $equipment->name
                ]);

                Notification::send("#VIEW#|eq_reserv:fail_create_record_mail", $incharge, [
                    '#TITLE#' => $subject,
                    'incharge' => "[[Q:{$incharge}]]",
                    'reserv' => "[[Q::{$reserv}]]"
                ]);
            }

            if (Config::get('eq_charge.foul_charge') && $equipment->charge_script['record']) {
                // 这里为了算计费，造了一个fake record，但是record id必填，所以使用了reserv的id并取反
                $record->id = -$reserv->id;
                $charge = O('eq_charge', ['source' => $record]);
                $charge->equipment = $equipment;
                $charge->source = $record;

                //如果修改了$record的user,则设定$charge->lab为$record->user->lab
                $charge->user = $record->user;
                $lab = Q("$charge->user lab")->current();
                if (!$charge->lab->id
                || $charge->user->id != $record->user->id
                || $record->project->lab->id != $charge->lab->id) {
                    $charge->lab = $lab;
                }

                $charge->calculate_amount()->save();
            }
        } else {
            /**
            *   2016-01-29 Unpc BUG#10663
            *   爽约的记录再生成记录时候需要同时更新反馈
            **/
            Event::trigger('try_create_record.before_save', $reserv, $record);
            $record->project = $reserv->project;
            $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
            $record->flag = $reserv->status;
            $record->feedback = I18N::T('eq_record', '系统自动生成记录进行反馈!');
            $record->save();
            Event::trigger('try_create_record.saved', $reserv, $record);
        }
    }

    private static function _check_eq_allowed_total_count_times($equipment, $user_v)
    {
        $user = $user_v->user;
        $max_eq_allowed_total_count_times = Lab::get('eq.max_allowed_total_count_times', Config::get('eq.max_allowed_total_count_times'), $equipment->group->name, true);

        if ($max_eq_allowed_total_count_times > 0 && $user_v->total_count >= $max_eq_allowed_total_count_times) {
            if (!$GLOBALS['preload']['people.multi_lab']) {
                $lab = Q("$user lab")->current();
            }

            $filter = ['user' => $user, 'object' => $equipment->group];
            if ($lab->id) {
                $filter['lab'] = $lab;
            } else {
                $filter['lab_id'] = 0;
            }

            $eq_banned = O('eq_banned', $filter);

            if (!$eq_banned->id) {
                if ($lab->id) {
                    $eq_banned->lab = $lab;
                }
                $eq_banned->user = $user;
                $eq_banned->object = $equipment->group;
                $eq_banned->reason = I18N::T('eq_ban', '使用设备违规总次数超过系统预定义上限!');
                $eq_banned->atime = 0;
                $eq_banned->save();
                Eq_Ban_Message::add($eq_banned);
            }
        }
    }

    private static function violation_exceed_preset($user)
    {
        if (Module::is_installed('eq_ban')) {
            $user_v = O('user_violation', ['user'=>$user]);
            if (!$user_v->id) {
                return;
            }
        }
        // 判断是否已经被加入黑名单 若已经加入，不进行其他判断
        if (EQ_Ban::is_user_banned($user)) {
            echo '用户已经在黑名单';
            return;
        }
        // 查询各类判断植
        $root_id = $user->group->root->id;
        $group = $user->group;
        // 迟到判断
        while (true) {
            $is_late_times_exceed = Lab::get('equipment.is_late_times_exceed', Config::get('equipment.is_late_times_exceed'), $group->name, true);
            $late_times_exceed_preset = Lab::get('equipment.late_times_exceed_preset', Config::get('equipment.late_times_exceed_preset'), $group->name, true);
            $group = $group->parent;
            if ($is_late_times_exceed === 'on' || $root_id == $group->id) {
                break;
            }
        }
        if (!($is_late_times_exceed === 'on')) {
            $is_late_times_exceed = Lab::get('equipment.is_late_times_exceed', Config::get('equipment.is_late_times_exceed'), 0);
            $late_times_exceed_preset = Lab::get('equipment.late_times_exceed_preset', Config::get('equipment.late_times_exceed_preset'), 0);
        }
        // 超时判断
        $root_id = $user->group->root->id;
        $group = $user->group;
        while (true) {
            $is_overtime_times_exceed = Lab::get('equipment.is_overtime_times_exceed', Config::get('equipment.is_overtime_times_exceed'), $group->name, true);
            $overtime_times_exceed_preset = Lab::get('equipment.overtime_times_exceed_preset', Config::get('equipment.overtime_times_exceed_preset'), $group->name, true);
            $group = $group->parent;
            if ($is_overtime_times_exceed === 'on' ||  $root_id == $group->id) {
                break;
            }
        }
        if (!($is_overtime_times_exceed === 'on')) {
            $is_overtime_times_exceed = Lab::get('equipment.is_overtime_times_exceed', Config::get('equipment.is_overtime_times_exceed'), 0);
            $overtime_times_exceed_preset = Lab::get('equipment.overtime_times_exceed_preset', Config::get('equipment.overtime_times_exceed_preset'), 0);
        }
        // 爽约判断
        $root_id = $user->group->root->id;
        $group = $user->group;
        while (true) {
            $is_miss_times_exceed = Lab::get('equipment.is_miss_times_exceed', Config::get('equipment.is_miss_times_exceed'), $group->name, true);
            $miss_times_exceed_preset = Lab::get('equipment.miss_times_exceed_preset', Config::get('equipment.miss_times_exceed_preset'), $group->name, true);
            $group = $group->parent;
            if ($is_miss_times_exceed === 'on' ||  $root_id == $group->id) {
                break;
            }
        }
        if (!($is_miss_times_exceed === 'on')) {
            $is_miss_times_exceed = Lab::get('equipment.is_miss_times_exceed', Config::get('equipment.is_miss_times_exceed'), 0);
            $miss_times_exceed_preset = Lab::get('equipment.miss_times_exceed_preset', Config::get('equipment.miss_times_exceed_preset'), 0);
        }
        // 早退
        $root_id = $user->group->root->id;
        $group = $user->group;
        while (true) {
            $is_leave_early_times_exceed = Lab::get('equipment.is_leave_early_times_exceed', Config::get('equipment.is_leave_early_times_exceed'), $group->name, true);
            $leave_early_times_exceed_preset = Lab::get('equipment.leave_early_times_exceed_preset', Config::get('equipment.leave_early_times_exceed_preset'), $group->name, true);
            $group = $group->parent;
            if ($is_leave_early_times_exceed === 'on' ||  $root_id == $group->id) {
                break;
            }
        }
        if (!($is_leave_early_times_exceed === 'on')) {
            $is_leave_early_times_exceed = Lab::get('equipment.is_leave_early_times_exceed', Config::get('equipment.is_leave_early_times_exceed'), 0);
            $leave_early_times_exceed_preset = Lab::get('equipment.leave_early_times_exceed_preset', Config::get('equipment.leave_early_times_exceed_preset'), 0);
        }
        // 违规行为
        $root_id = $user->group->root->id;
        $group = $user->group;
        while (true) {
            $is_violate_times_exceed = Lab::get('equipment.is_violate_times_exceed', Config::get('equipment.is_violate_times_exceed'), $group->name, true);
            $violate_times_exceed_preset = Lab::get('equipment.violate_times_exceed_preset', Config::get('equipment.violate_times_exceed_preset'), $group->name, true);
            $group = $group->parent;
            if ($is_violate_times_exceed === 'on' ||  $root_id == $group->id) {
                break;
            }
        }
        if (!($is_violate_times_exceed === 'on')) {
            $is_violate_times_exceed = Lab::get('equipment.is_violate_times_exceed', Config::get('equipment.is_violate_times_exceed'), 0);
            $violate_times_exceed_preset = Lab::get('equipment.violate_times_exceed_preset', Config::get('equipment.violate_times_exceed_preset'), 0);
        }
        // 违规总次数
        $root_id = $user->group->root->id;
        $group = $user->group;
        while (true) {
            $is_total_count_times_exceed = Lab::get('equipment.is_total_count_times_exceed', Config::get('equipment.is_total_count_times_exceed'), $group->name, true);
            $total_count_times_exceed_preset = Lab::get('equipment.total_count_times_exceed_preset', Config::get('equipment.total_count_times_exceed_preset'), $group->name, true);
            $group = $group->parent;
            if ($is_total_count_times_exceed === 'on' ||  $root_id == $group->id) {
                break;
            }
        }
        if (!($is_total_count_times_exceed === 'on')) {
            $is_total_count_times_exceed = Lab::get('equipment.is_total_count_times_exceed', Config::get('equipment.is_total_count_times_exceed'), 0);
            $total_count_times_exceed_preset = Lab::get('equipment.total_count_times_exceed_preset', Config::get('equipment.total_count_times_exceed_preset'), 0);
        }

        $reason = [];
        if ($is_late_times_exceed === 'on' && $late_times_exceed_preset > 0 && $user_v->eq_late_count >= $late_times_exceed_preset) {
            // 满足迟到阈值
            $reason[] = "迟到";
        }
        if ($is_miss_times_exceed === 'on' && $miss_times_exceed_preset > 0 && $user_v->eq_miss_count >= $miss_times_exceed_preset) {
            // 满足爽约阈值
            $reason[] = "爽约";
        }
        if ($is_leave_early_times_exceed === 'on' && $leave_early_times_exceed_preset > 0 && $user_v->eq_leave_early_count >= $leave_early_times_exceed_preset) {
            // 满足早退阈值
            $reason[] = "早退";
        }
        if ($is_overtime_times_exceed === 'on' && $overtime_times_exceed_preset > 0 && $user_v->eq_overtime_count >= $overtime_times_exceed_preset) {
            // 满足超时阈值
            $reason[] = "超时";
        }
        if ($is_violate_times_exceed === 'on'
            && $violate_times_exceed_preset > 0
            && $user_v->eq_violate_count >= $violate_times_exceed_preset) {
            // 满足超时阈值
            $reason[] = "违规行为";
        }
        if ($is_total_count_times_exceed === 'on'
            && $total_count_times_exceed_preset > 0
            && ($user_v->eq_miss_count + $user_v->eq_leave_early_count + $user_v->eq_overtime_count + $user_v->eq_late_count + $user_v->eq_violate_count) >= $total_count_times_exceed_preset) {
            // 满足超时阈值
            $reason[] = "违规总次数";
        }
        if (count($reason) > 0) {
            Notification::send('eq_reserv.violation.exceed_preset', $user, [
                '%user' => Markup::encode_Q($user),
                '%number' => $user_v->total_count,
                '%reason' => implode("、", $reason)
            ]);
        }
    }
}
