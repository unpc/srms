<?php
class EQ_Empower {

	static function empower_setting_breadcrumb($e, $equipment, $type) {
        if ($type != 'reserv') return;

        $e->return_value = [
            [
                'url'=> $equipment->url(),
                'title'=> H($equipment->name)
            ],
            [
                'url'=> $equipment->url(NULL, NULL, NULL, 'edit'),
                'title'=> I18N::T('eq_empower', '设置')
            ],
            [
                'url'=> $equipment->url('reserv', NULL, NULL, 'empower_setting'),
                'title'=> I18N::T('eq_empower', '工作时间设置')
            ]
        ];
    }

	static function empower_setting_content($e, $equipment, $type) {
        if ($type != 'reserv') return;

        $me = L('ME');
        if (!$me->is_allowed_to('查看预约设置', $equipment)) {
            URI::redirect('error/401');
        }
        $readonly = !$me->is_allowed_to('修改预约设置', $equipment);

		$form = Form::filter(Input::form());
        $success = 0;
        $fails = 0;
        
		if ($form['submit']) {
            if ($readonly) {
                Lab::message(Lab::MESSAGE_ERROR,I18N::T('eq_sample','预约工作时间更新失败!'));
                URI::redirect();
            }
            Q("eq_reserv_time[equipment={$equipment}]")->delete_all();
    
            foreach ($form['startdate'] as $key => $value) {
                $fail = 0;
                $time = O('eq_reserv_time', $form['id'][$key]);
                $time->equipment = $equipment;
                $rules = [];

                $time->controlall = $form['controlall'][$key];
                if ($time->controlall) {
                    $time->controluser = '';
                    $time->controllab = '';
                    $time->controlgroup = '';
                }
                else {
                    $time->controluser = ($form['select_user_mode_user'][$key] == 'on' && $form['user'][$key] != '{}')
                        ? $form['user'][$key] : '';
                    
                    $time->controllab = ($form['select_user_mode_lab'][$key] == 'on' && $form['lab'][$key] != '{}')
                        ? $form['lab'][$key] : '';
    
                    $time->controlgroup = ($form['select_user_mode_group'][$key] == 'on' && $form['group'][$key] != '{}')
                        ? $form['group'][$key] : '';

                    if ($time->controluser == '' && $time->controllab == '' && $time->controlgroup == '') {
                        $form->set_error('controlall['.$key.']', I18N::T('eq_sample', '选择个别用户后请选择具体使用用户!'));
                        $fail ++;
                    }
                }

                if ($form['starttime'][$key] >= 31593600) $form['starttime'][$key] = $form['starttime'][$key] - 86400;
                elseif ($form['starttime'][$key] < 31507200) $form['starttime'][$key] = $form['starttime'][$key] + 86400;
                if ($form['endtime'][$key] >= 31593600) $form['endtime'][$key] = $form['endtime'][$key] - 86400;
                elseif ($form['endtime'][$key] < 31507200) $form['endtime'][$key] = $form['endtime'][$key] + 86400;

                if ($form['startdate'][$key] > $form['enddate'][$key]) {
                    $form->set_error('working_date['.$key.']', I18N::T('eq_sample', '起始日期不能大于结束日期!'));
                    $fail ++;
                }

                if ($form['starttime'][$key] >= $form['endtime'][$key]) {
                    $form->set_error('working_time['.$key.']', I18N::T('eq_sample', '起始时间不能大于等于结束时间!'));
                    $fail ++;
                }

                $time->ltstart = mktime(0, 0, 0, date('m', $form['startdate'][$key]), date('d', $form['startdate'][$key]), date('Y', $form['startdate'][$key]));
                $time->ltend = mktime(23, 59, 59, date('m', $form['enddate'][$key]), date('d', $form['enddate'][$key]), date('Y', $form['enddate'][$key]));
                $time->dtstart = mktime(date('H', $form['starttime'][$key]), date('i', $form['starttime'][$key]), date('s', $form['starttime'][$key]), 1, 1, 1971);
                $time->dtend = mktime(date('H', $form['endtime'][$key]), date('i', $form['endtime'][$key]), date('s', $form['endtime'][$key]), 1, 1, 1971);
                $time->type = $form['repeat'][$key] ? $form['rtype'][$key] : 1;
                $time->num = $form['repeat'][$key] ? $form['rnum'][$key] : 1;
                
                switch($time->type) {
                    case -2:    //用户选择工作日，默认为周一到周五
                        $rules = [1,2,3,4,5];
                        break;
                    case -3:    //用户选择周末，默认为周六周日
                        $rules = [0,6];
                        break;
                    case 2:
                        $rules = array_keys($form['week_day'][$key] ? : []);
                        if (!$rules) {
                            $form->set_error('rule_form_'.$key, I18N::T('eq_sample', '请选择预约时间间隔的具体星期!'));
                            $fail ++;
                        }
                        break;
                    case 3:
                        $rules = array_keys($form['month_day'][$key] ? : []);
                        if (!$rules) {
                            $form->set_error('rule_form_'.$key, I18N::T('eq_sample', '请选择预约时间间隔的具体日期!'));
                            $fail ++;
                        }
                        break;
                    case 4:
                        $rules = array_keys($form['year_month'][$key] ? : []);
                        if (!$rules) {
                            $form->set_error('rule_form_'.$key, I18N::T('eq_sample', '请选择预约时间间隔的具体月份!'));
                            $fail ++;
                        }
                        break;
                }
                $time->days = $rules;

                if (!$fail && $time->save()) {
                    $success++ ;
                    Log::add(strtr('[eq_sample] %user_name[%user_id] 修改%equipment_name[%equipment_id]预约时间的规则', [
                        '%user_name' => L('ME')->name,
                        '%user_id' => L('ME')->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id' => $equipment->id,
                    ]), 'journal');
                }
                else $fails ++;
                
                $ntime['id'] = $time->id;
                $ntime['equipment'] = $time->equipment->id;
                $ntime['startdate'] = $time->ltstart;
                $ntime['enddate'] = $time->ltend;
                $ntime['starttime'] = $time->dtstart;
                $ntime['endtime'] = $time->dtend;
                $ntime['rtype'] = $time->type;
                $ntime['rnum'] = $time->num;
                $ntime['days'] = $time->days;
                $ntime['controlall'] = $time->controlall;
                $ntime['controluser'] = $time->controluser;
                $ntime['controllab'] = $time->controllab;
                $ntime['controlgroup'] = $time->controlgroup;
                $times[] = $ntime;
            }

            if ($success && !$fails) {
                Lab::message(Lab::MESSAGE_NORMAL,I18N::T('eq_sample','预约工作时间更新成功!'));
            }
            elseif ($success && $fails) {
                Lab::message(Lab::MESSAGE_NORMAL,I18N::T('eq_sample','预约工作时间部分更新成功!'));
            }
            elseif (!$form->no_error) {
                Lab::message(Lab::MESSAGE_ERROR,I18N::T('eq_sample','预约工作时间更新失败!'));
            }
        }
        else {
            $times = [];
            $sample_times = Q("eq_reserv_time[equipment={$equipment}]");

            foreach ($sample_times as $key => $value) {
                $time = [];
                $time['id'] = $value->id;
                $time['equipment'] = $value->equipment->id;
                $time['startdate'] = $value->ltstart;
                $time['enddate'] = $value->ltend;
                $time['starttime'] = $value->dtstart;
                $time['endtime'] = $value->dtend;
                $time['rtype'] = $value->type;
                $time['rnum'] = $value->num;
                $time['days'] = explode(',', $value->days);
                $time['controlall'] = $value->controlall;
                $time['controluser'] = $value->controluser;
                $time['controllab'] = $value->controllab;
                $time['controlgroup'] = $value->controlgroup;
                $times[] = $time;
            }
		}
        if ($readonly) {
            $e->return_value = (string) V('eq_empower:empower_setting_readonly', ['times' => $times, 'form' => $form]);
        }
        else {
            $e->return_value = (string) V('eq_empower:empower_setting', ['times' => $times, 'form' => $form]);
        }
    }

    static function empower_setting_content_submit($e,$equipment,$form) {
        $me = L('ME');
        $success = 0;
        $fails = 0;
        $readonly = !$me->is_allowed_to('修改预约设置', $equipment);
        if ($readonly) {
            Lab::message(Lab::MESSAGE_ERROR,I18N::T('eq_sample','预约工作时间更新失败!'));
            URI::redirect();
        }
        Q("eq_reserv_time[equipment={$equipment}]")->delete_all();

        foreach ($form['startdate'] as $key => $value) {
            $fail = 0;
            $time = O('eq_reserv_time', $form['id'][$key]);
            $time->equipment = $equipment;
            $rules = [];

            $time->controlall = $form['controlall'][$key];
            if ($time->controlall) {
                $time->controluser = '';
                $time->controllab = '';
                $time->controlgroup = '';
            }
            else {
                $time->controluser = ($form['select_user_mode_user'][$key] == 'on' && $form['user'][$key] != '{}')
                    ? $form['user'][$key] : '';
                
                $time->controllab = ($form['select_user_mode_lab'][$key] == 'on' && $form['lab'][$key] != '{}')
                    ? $form['lab'][$key] : '';

                $time->controlgroup = ($form['select_user_mode_group'][$key] == 'on' && $form['group'][$key] != '{}')
                    ? $form['group'][$key] : '';

                if ($time->controluser == '' && $time->controllab == '' && $time->controlgroup == '') {
                    $form->set_error('controlall['.$key.']', I18N::T('eq_sample', '选择个别用户后请选择具体使用用户!'));
                    $fail ++;
                }
            }

            if ($form['starttime'][$key] >= 31593600) $form['starttime'][$key] = $form['starttime'][$key] - 86400;
            elseif ($form['starttime'][$key] < 31507200) $form['starttime'][$key] = $form['starttime'][$key] + 86400;
            if ($form['endtime'][$key] >= 31593600) $form['endtime'][$key] = $form['endtime'][$key] - 86400;
            elseif ($form['endtime'][$key] < 31507200) $form['endtime'][$key] = $form['endtime'][$key] + 86400;

            if ($form['startdate'][$key] > $form['enddate'][$key]) {
                $form->set_error('working_date['.$key.']', I18N::T('eq_sample', '起始日期不能大于结束日期!'));
                $fail ++;
            }

            if ($form['starttime'][$key] >= $form['endtime'][$key]) {
                $form->set_error('working_time['.$key.']', I18N::T('eq_sample', '起始时间不能大于等于结束时间!'));
                $fail ++;
            }

            $time->ltstart = mktime(0, 0, 0, date('m', $form['startdate'][$key]), date('d', $form['startdate'][$key]), date('Y', $form['startdate'][$key]));
            $time->ltend = mktime(23, 59, 59, date('m', $form['enddate'][$key]), date('d', $form['enddate'][$key]), date('Y', $form['enddate'][$key]));
            $time->dtstart = mktime(date('H', $form['starttime'][$key]), date('i', $form['starttime'][$key]), date('s', $form['starttime'][$key]), 1, 1, 1971);
            $time->dtend = mktime(date('H', $form['endtime'][$key]), date('i', $form['endtime'][$key]), date('s', $form['endtime'][$key]), 1, 1, 1971);
            $time->type = $form['repeat'][$key] ? $form['rtype'][$key] : 1;
            $time->num = $form['repeat'][$key] ? $form['rnum'][$key] : 1;
            
            switch($time->type) {
                case -2:    //用户选择工作日，默认为周一到周五
                    $rules = [1,2,3,4,5];
                    break;
                case -3:    //用户选择周末，默认为周六周日
                    $rules = [0,6];
                    break;
                case 2:
                    $rules = array_keys($form['week_day'][$key] ? : []);
                    if (!$rules) {
                        $form->set_error('rule_form_'.$key, I18N::T('eq_sample', '请选择预约时间间隔的具体星期!'));
                        $fail ++;
                    }
                    break;
                case 3:
                    $rules = array_keys($form['month_day'][$key] ? : []);
                    if (!$rules) {
                        $form->set_error('rule_form_'.$key, I18N::T('eq_sample', '请选择预约时间间隔的具体日期!'));
                        $fail ++;
                    }
                    break;
                case 4:
                    $rules = array_keys($form['year_month'][$key] ? : []);
                    if (!$rules) {
                        $form->set_error('rule_form_'.$key, I18N::T('eq_sample', '请选择预约时间间隔的具体月份!'));
                        $fail ++;
                    }
                    break;
            }
            $time->days = $rules;

            if (!$fail && $time->save()) {
                $success++ ;
                Log::add(strtr('[eq_sample] %user_name[%user_id] 修改%equipment_name[%equipment_id]预约时间的规则', [
                    '%user_name' => L('ME')->name,
                    '%user_id' => L('ME')->id,
                    '%equipment_name' => $equipment->name,
                    '%equipment_id' => $equipment->id,
                ]), 'journal');
            }
            else $fails ++;
            
            // $ntime['id'] = $time->id;
            // $ntime['equipment'] = $time->equipment->id;
            // $ntime['startdate'] = $time->ltstart;
            // $ntime['enddate'] = $time->ltend;
            // $ntime['starttime'] = $time->dtstart;
            // $ntime['endtime'] = $time->dtend;
            // $ntime['rtype'] = $time->type;
            // $ntime['rnum'] = $time->num;
            // $ntime['days'] = $time->days;
            // $ntime['controlall'] = $time->controlall;
            // $ntime['controluser'] = $time->controluser;
            // $ntime['controllab'] = $time->controllab;
            // $ntime['controlgroup'] = $time->controlgroup;
            // $times[] = $ntime;
        }
        // $form->times = $times;

    }

	static function check_add_workingtime($e, $equipment, $dtstart, $dtend, $user=null) {
		$e->return_value = self::check_workingtime($equipment, $dtstart, $dtend, $user);
	}

	static function check_workingtime($equipment, $dtstart, $dtend, $user=null, $empower=null) {
        $me = L('ME');
        if (!$user) $user = $me;

        //这为了区分是否是无权申请
        $eq_reserv_times = Q("eq_reserv_time[equipment={$equipment}][ltstart=$dtstart~$dtend|ltend=$dtstart~$dtend|ltstart~ltend=$dtstart|ltstart~ltend=$dtend]");
        
        if (Config::get('eq_reserv.add_ignore_reserv_time') && $me->is_allowed_to('修改', $equipment)) {
            return true;
        }

        if ($user->is_allowed_to('修改', $equipment) || $eq_reserv_times->total_count() == 0) {
            return true;
        }

        $ids = [];

        // 转换预约时间，方便匹配规则
        $times = mktime(date('H', $dtstart), date('i', $dtstart), date('s', $dtstart), 1, 1, 1971);
        $timee = mktime(date('H', $dtend), date('i', $dtend), date('s', $dtend), 1, 1, 1971);
        $dates = strtotime(date('Y-m-d', $dtstart));
        $datee = strtotime(date('Y-m-d', $dtend));
        
        foreach ($eq_reserv_times as $eq_reserv_time) {
            if (!$eq_reserv_time->check_user($user)) continue;

            $diff = date('d', $dates) - date('d', $datee);
            if ($diff) {
                $days = [
                    ['dates' => $dates, 'times' => $times, 'datee' => $dates, 'timee' => 31507200],
                    ['dates' => $datee, 'times' => 31507200, 'datee' => $datee, 'timee' => $timee]
                ];

                if ($diff > 1) for ($i = 1; $i < $diff ; $i++) { 
                    $days[] = [
                        'dates' => Date::next_time($dates, $i),
                        'times' => 31507200,
                        'datee' => Date::next_time($dates, $i),
                        'timee' => 31593599,
                    ];
                }
                
                foreach ($days as $day) {
                    if ($eq_reserv_time->check_time($day['dates'], $day['times']) 
                    && $eq_reserv_time->check_time($day['datee'], $day['timee'])) return true;
                }
            }
            else {
                if ($eq_reserv_time->check_time($dates, $times) 
                && $eq_reserv_time->check_time($datee, $timee)) return true;
            }
        }

        return false;
	}

	static function check_component_workingtime($e, $equipment, $params) {
		$user = $params[0];
		$dtstart = $params[1];
		$dtend = $params[2];
		$isallowed = true;
		//只判断给定时间的操作，如果未给定时间设置为允许添加
		if ($dtstart && $dtend) {
			$isallowed = self::check_workingtime($equipment, $dtstart, $dtend, $user);
			if (!$isallowed) Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_empower', '非工作时间内不允许添加使用预约.'));
		}
        $e->return_value = !$isallowed;
	}

    static function workingtime_sort($times, $key) {
        //循环二维数组 进行开始时间排序
        //添加非工作时间是先添加 今天的开始=>工作时间1 工作时间1=>工作时间2 ... 需要顺序
        usort($times, function (array $a, array $b) use ($key) {
            return $a[$key] <=> $b[$key];
        });
        return $times; 
    }

	static function get_workingtime($e, $calendar, $dtstart, $dtend, $form) {
        $me = L('ME');
        $cdata = [];
        $times = [];
        /**
         * 这里要考虑两个问题
         * 1. meeting 模块的工作时间预约块显示
         * 2. eq_sample 预约日历的工作时间显示
         */
        if ($calendar->parent->id) {
            if ($calendar->parent->name() == 'equipment' && $calendar->type != 'eq_reserv') {
                $e->return_value = $cdata;
                return true;
            }

            if ($calendar->parent->name() == 'meeting') {
                // 这里不执行任何代码，只是响应上面的注释打个标记，类似于往下goto一样，表明考虑到了meeting模块
            }
        }

        $parent = $calendar->parent;
        $parent_name = $calendar->parent->name();

        //获取与当前时间有交集的工作时间
        $eq_reserv_times = Q("eq_reserv_time[{$parent_name}={$parent}][ltstart=$dtstart~$dtend|ltend=$dtstart~$dtend|ltstart~ltend=$dtstart|ltstart~ltend=$dtend]");
		if ($form['start'] || $form['step']) $eq_reserv_times = $eq_reserv_times->limit($form['start'], $form['step']);
        //不受限用户看不到非工作时间
        if ($me->is_allowed_to('修改', $parent)) {
            $e->return_value = $cdata;
            return FALSE;
        }


        foreach ($eq_reserv_times as $eq_reserv_time) {
            // 如果用户不符合这个工作时间条件 视为这段时间无法使用
            $off = !$eq_reserv_time->check_user($me);

            //确认范围
            $begin = max($eq_reserv_time->ltstart, $dtstart);
            $end = min($eq_reserv_time->ltend, $dtend);
            
            switch ($eq_reserv_time->type) {
                case WT_RRule::RRULE_DAILY:
                    while ($begin <= $end) {
                        $diff = date_diff(date_create(date('Y-m-d', $eq_reserv_time->ltstart)), date_create(date('Y-m-d', $begin)));
                        $week = date('w', $begin);
                        //确认间隔工作日与当前日子是否一致
                        if ($diff->d % $eq_reserv_time->num != 0 || $off) {
                            if (!$times[$week]) {
                                //off代表全天非工作时间
                                $times[$week] = [
                                    'type' => 'off',
                                    'start' => $begin,
                                    'end' => Date::next_time($begin) - 1,
                                ];
                            }
                        }
                        else {
                            //如果有工作时间 要先将之前的全天非工作时间删除
                            unset($times[$week]['type']);
                            unset($times[$week]['start']);
                            unset($times[$week]['end']);
                            $times[$week] = $eq_reserv_time->clipping($times[$week], $begin);
                        }
                        $begin = Date::next_time($begin);
                    }
                    break;
                case WT_RRule::RRULE_WEEKDAY:
                case WT_RRule::RRULE_WEEKEND_DAY:
                case WT_RRule::RRULE_WEEKLY:
                    $days = explode(',', $eq_reserv_time->days);
                    while ($begin <= $end) {
                        $week = date('w', $begin);
                        $diff = abs(date('W', $begin) - date('W', $eq_reserv_time->ltstart));
                        if (!in_array($week, $days)
                        || $diff % $eq_reserv_time->num != 0
                        || $off) {
                            if (!$times[$week]) {
                                $times[$week] = [
                                    'type' => 'off',
                                    'start' => $begin,
                                    'end' => Date::next_time($begin) - 1,
                                ];
                            }
                        }
                        else {
                            unset($times[$week]['type']);
                            unset($times[$week]['start']);
                            unset($times[$week]['end']);
                            $times[$week] = $eq_reserv_time->clipping($times[$week], $begin);
                        }
                        $begin = Date::next_time($begin);
                    }
                    break;
                case WT_RRule::RRULE_MONTHLY:
                    $days = explode(',', $eq_reserv_time->days);
                    while ($begin <= $end) {
                        $week = date('w', $begin);
                        $diff = abs(date('m', $begin) - date('m', $eq_reserv_time->ltstart));
                        if (!in_array(date('d', $begin), $days)
                        || $diff % $eq_reserv_time->num != 0
                        || $off) {
                            if (!$times[$week]) {
                                $times[$week] = [
                                    'type' => 'off',
                                    'start' => $begin,
                                    'end' => Date::next_time($begin) - 1,
                                ];
                            }
                        }
                        else {
                            unset($times[$week]['type']);
                            unset($times[$week]['start']);
                            unset($times[$week]['end']);
                            $times[$week] = $eq_reserv_time->clipping($times[$week], $begin);
                        }
                        $begin = Date::next_time($begin);
                    }
                    break;
                case WT_RRule::RRULE_YEARLY:
                    $days = explode(',', $eq_reserv_time->days);
                    while ($begin <= $end) {
                        $week = date('w', $begin);
                        $diff = abs(date('Y', $begin) - date('Y', $eq_reserv_time->ltstart));
                        if (!in_array(date('m', $begin), $days)
                        || $diff % $eq_reserv_time->num != 0
                        || $off) {
                            if (!$times[$week]) {
                                $times[$week] = [
                                    'type' => 'off',
                                    'start' => $begin,
                                    'end' => Date::next_time($begin) - 1,
                                ];
                            }
                        }
                        else {
                            unset($times[$week]['type']);
                            unset($times[$week]['start']);
                            unset($times[$week]['end']);
                            $times[$week] = $eq_reserv_time->clipping($times[$week], $begin);
                        }
                        $begin = Date::next_time($begin);
                    }
                    break;
                default:
                    break;
            }
        }

        foreach ($times as $week) {
            if ($week['type'] == 'off') {
                $cdata[] = [
                    'id' => 0,
                    'dtStart' => $week['start'],
                    'dtEnd' => $week['end'],
                    'color' => 9,
                    'calendar' => $calendar,
                    'content' => (string)V('eq_empower:calendar/not_work_time', []),
                ];
            }
            else {
                if ($week) $week = self::workingtime_sort($week, 'start');
                $start = null;
                if ($week) foreach ($week as $time) {
                    $start = $start ? : Date::get_day_start($time['start']);
                    
                    if ($start < $time['start']) {
                        $cdata[] = [
                            'id' => 0,
                            'dtStart' => $start,
                            'dtEnd' => $time['start'] - 1,
                            'color' => 9,
                            'calendar' => $calendar,
                            'content' => (string)V('eq_empower:calendar/not_work_time', []),
                        ];
                    }
                    $start = $time['end'];
                }
                
                if ($start && $start != Date::get_day_end($time['start'])) {
                    $cdata[] = [
                        'id' => 0,
                        'dtStart' => $start,
                        'dtEnd' => Date::get_day_end($time['start']) - 1,
                        'color' => 9,
                        'calendar' => $calendar,
                        'content' => (string)V('eq_empower:calendar/not_work_time', []),
                    ];
                }
            }
        }
        
		$e->return_value = $cdata;
		return FALSE;
	}

}
