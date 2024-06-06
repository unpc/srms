<?php
class EQ_Sample_Time
{

    public static function time_setting_breadcrumb($e, $equipment, $type)
    {
        if ($type != 'sample') {
            return;
        }

        $e->return_value = [
            [
                'url' => $equipment->url(),
                'title' => H($equipment->name),
            ],
            [
                'url' => $equipment->url(null, null, null, 'edit'),
                'title' => I18N::T('eq_sample', '设置'),
            ],
            [
                'url' => $equipment->url('use', null, null, 'time_setting'),
                'title' => I18N::T('eq_sample', '送样时间'),
            ],
        ];
    }

    public static function time_setting_content($e, $equipment, $type)
    {
        if ($type != 'sample') {
            return;
        }

        $me = L('ME');
        if (!$me->is_allowed_to('修改送样设置', $equipment)) {
            URI::redirect('error/401');
        }

        $form = Form::filter(Input::form());
        $success = 0;
        $fail = 0;
        $fails = 0;

        if ($form['submit']) {
            Q("eq_sample_time[equipment={$equipment}]")->delete_all();

            /* $ids = Q("eq_sample_time[equipment={$equipment}]")->to_assoc('id', 'id');
            foreach ($ids as $id) {
                if (!in_array($id, $form['id'])) {
                    O('eq_sample_time', $id)->delete();
                    $success++;
                }
            } */

            foreach ($form['startdate'] as $key => $value) {
                $time = O('eq_sample_time', $form['id'][$key]);
                $rules = [];

                if (!$time->id) {
                    $time = O('eq_sample_time');
                    $time->equipment = $equipment;
                }

                $time->uncontrolall = $form['uncontrolall'][$key];

                if (!$form['uncontrolall'][$key]) {
                    if ($form['select_user_mode_user'][$key] == 'on' && $form['user'][$key] != '{}') {
                        $time->uncontroluser = $form['user'][$key];
                    } else {
                        $time->uncontroluser = '';
                    }

                    if ($form['select_user_mode_lab'][$key] == 'on' && $form['lab'][$key] != '{}') {
                        $time->uncontrollab = $form['lab'][$key];
                    } else {
                        $time->uncontrollab = '';
                    }

                    if ($form['select_user_mode_group'][$key] == 'on' && $form['group'][$key] != '{}') {
                        $time->uncontrolgroup = $form['group'][$key];
                    } else {
                        $time->uncontrolgroup = '';
                    }
                } else {
                    $time->uncontrolgroup = $time->uncontrollab = $time->uncontroluser = '';
                }

                if ($form['startdate'][$key] > $form['enddate'][$key]) {
                    $form->set_error('working_date[' . $key . ']', I18N::T('eq_sample', '起始日期不能大于结束日期!'));
                    $fail++;
                }
                if ($form['starttime'][$key] > $form['endtime'][$key]) {
                    $form->set_error('working_time[' . $k . ']', I18N::T('eq_sample', '起始时间不能大于结束时间!'));
                    $fail++;
                }
                $time->ltstart = mktime(0, 0, 0, date('m', $form['startdate'][$key]), date('d', $form['startdate'][$key]), date('Y', $form['startdate'][$key]));
                $time->ltend = mktime(23, 59, 59, date('m', $form['enddate'][$key]), date('d', $form['enddate'][$key]), date('Y', $form['enddate'][$key]));
                $time->dtstart = mktime(date('H', $form['starttime'][$key]), date('i', $form['starttime'][$key]), date('s', $form['starttime'][$key]), 1, 1, 1971);
                $time->dtend = mktime(date('H', $form['endtime'][$key]), date('i', $form['endtime'][$key]), date('s', $form['endtime'][$key]), 1, 1, 1971);
                $time->type = $form['repeat'][$key] ? $form['rtype'][$key] : 1;
                $time->num = $form['repeat'][$key] ? $form['rnum'][$key] : 1;
                switch ($time->type) {
                    case -2: //用户选择工作日，默认为周一到周五
                        $rules = [1, 2, 3, 4, 5];
                        break;
                    case -3: //用户选择周末，默认为周六周日
                        $rules = [0, 6];
                        break;
                    case 2:
                        $rules = array_keys($form['week_day'][$key] ?: []);
                        if (!$rules) {
                            $form->set_error('rule_form_' . $key, I18N::T('eq_sample', '请选择送样时间间隔的具体星期!'));
                            $fail++;
                        }
                        break;
                    case 3:
                        $rules = array_keys($form['month_day'][$key] ?: []);
                        if (!$rules) {
                            $form->set_error('rule_form_' . $key, I18N::T('eq_sample', '请选择送样时间间隔的具体日期!'));
                            $fail++;
                        }
                        break;
                    case 4:
                        $rules = array_keys($form['year_month'][$key] ?: []);
                        if (!$rules) {
                            $form->set_error('rule_form_' . $key, I18N::T('eq_sample', '请选择送样时间间隔的具体月份!'));
                            $fail++;
                        }
                        break;
                }
                $time->days = $rules;

                if (!$fail && $time->save()) {
                    $success++;
                    Log::add(strtr('[eq_sample] %user_name[%user_id] 修改%equipment_name[%equipment_id]送样时间的规则', [
                        '%user_name' => L('ME')->name,
                        '%user_id' => L('ME')->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id' => $equipment->id,
                    ]), 'journal');
                } else {
                    $fails++;
                }

                $ntime['id'] = $time->id;
                $ntime['equipment'] = $time->equipment->id;
                $ntime['startdate'] = $time->ltstart;
                $ntime['enddate'] = $time->ltend;
                $ntime['starttime'] = $time->dtstart;
                $ntime['endtime'] = $time->dtend;
                $ntime['rtype'] = $time->type;
                $ntime['rnum'] = $time->num;
                $ntime['days'] = $time->days;
                $ntime['uncontroluser'] = $time->uncontroluser;
                $ntime['uncontrollab'] = $time->uncontrollab;
                $ntime['uncontrolgroup'] = $time->uncontrolgroup;
                $ntime['uncontrolall'] = $time->uncontrolall;
                $times[] = $ntime;
            }

            if ($success && !$fails) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_sample', '送样时间更新成功!'));
            } else if ($success && $fails) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_sample', '送样时间部分更新成功!'));
            } else {
                if ($form->no_error) {
                    // Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '送样时间更新失败!'));
                }
            }
        } else {
            $time = [];
            $times = [];
            $sample_times = Q("eq_sample_time[equipment={$equipment}]");

            foreach ($sample_times as $key => $value) {
                $time['id'] = $value->id;
                $time['equipment'] = $value->equipment->id;
                $time['startdate'] = $value->ltstart;
                $time['enddate'] = $value->ltend;
                $time['starttime'] = $value->dtstart;
                $time['endtime'] = $value->dtend;
                $time['rtype'] = $value->type;
                $time['rnum'] = $value->num;
                $time['days'] = explode(',', $value->days);
                $time['uncontroluser'] = $value->uncontroluser;
                $time['uncontrollab'] = $value->uncontrollab;
                $time['uncontrolgroup'] = $value->uncontrolgroup;
                $time['uncontrolall'] = $value->uncontrolall;
                $times[] = $time;
            }
        }

        $e->return_value = $times;
        // $e->return_value = (string)V('eq_sample_time:edit/sample_time', ['times'=> $times, 'form' => $form]);
    }

    public static function on_eq_sample_before_save($e, $sample, $data)
    {
        $e->return_value = self::check_workingtime($sample->equipment, $sample->dtsubmit);
        return false;
    }

    public static function check_workingtime($equipment, $date, $user = null, $empower = null)
    {
        if (!$user) {
            $user = L('ME');
        }

        if ($user->is_allowed_to('修改', $equipment)
            || Q("eq_sample_time[equipment={$equipment}]")->total_count() == 0) {
            return true;
        }

        //这为了区分是否是无权申请
        $is_allowed_user = false;
        $ids = [];
        $time = mktime(date('H', $date), date('i', $date), date('s', $date), 1, 1, 1971);
        $eq_sample_times = Q("eq_sample_time[equipment={$equipment}][ltstart<=$date][ltend>=$date]");
        $date = strtotime(date('Y-m-d', $date));
        foreach ($eq_sample_times as $eq_sample_time) {
            $users = array_keys(json_decode($eq_sample_time->uncontroluser, true));
            if (in_array($user->id, $users)) {
                $is_allowed_user = true;
                $ids[] = $eq_sample_time->id;
            }

            $labs = array_keys(json_decode($eq_sample_time->uncontrollab, true));
            if (!in_array($eq_sample_time->id, $ids) && in_array(Q("$user lab")->current()->id, $labs)) {
                $is_allowed_user = true;
                $ids[] = $eq_sample_time->id;
            }

            $groups = array_keys(json_decode($eq_sample_time->uncontrolgroup, true));
            foreach ($groups as $group) {
                $group = O('tag_group', $group);
                if (!in_array($eq_sample_time->id, $ids) && $group->is_itself_or_ancestor_of($user->group)) {
                    $is_allowed_user = true;
                    $ids[] = $eq_sample_time->id;
                }
            }
        }

        $is_allowed_time = false;
        if (!$is_allowed_user) {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '您无权在此送样时间进行送样，请选择您可以送样的时间，如有疑问，请联系仪器负责人!'));
            return false;
        } else {
            $ids = join(',', $ids);
            $eq_sample_times = Q("eq_sample_time[id=$ids]");
            foreach ($eq_sample_times as $eq_sample_time) {
                $num = $eq_sample_time->num;
                $diff1 = date_create(date('Y-m-d', $date));
                $diff2 = date_create(date('Y-m-d', $eq_sample_time->dtstart));
                $diff = date_diff($diff1, $diff2);
                $days = explode(',', $eq_sample_time->days);
                switch ($eq_sample_time->type) {
                    case 1:
                        if ($diff->d % $eq_sample_time->num == 0) {
                            if ($time >= $eq_sample_time->dtstart && $time <= $eq_sample_time->dtend) {
                                $is_allowed_time = true;
                                break;
                            }
                        }
                        break;
                    case -2:
                    case -3:
                    case 2:
                        $diff->w = abs(date('W', $date) - date('W', $eq_sample_time->dtstart));
                        if (($diff->w % $eq_sample_time->num) == 0 && in_array(date('w', $date), $days)) {
                            if ($time >= $eq_sample_time->dtstart && $time <= $eq_sample_time->dtend) {
                                $is_allowed_time = true;
                                break;
                            }
                        }
                        break;
                    case 3:
                        if (($diff->m % $eq_sample_time->num) == 0 && in_array(date('d', $date), $days)) {
                            if ($time >= $eq_sample_time->dtstart && $time <= $eq_sample_time->dtend) {
                                $is_allowed_time = true;
                                break;
                            }
                        }
                        break;
                    case 4:
                        if (($diff->y % $eq_sample_time->num) == 0 && in_array(date('m', $date), $days)) {
                            if ($time >= $eq_sample_time->dtstart && $time <= $eq_sample_time->dtend) {
                                $is_allowed_time = true;
                                break;
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        if ($is_allowed_time) {
            return true;
        } else {
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '请选择您可以送样的时间! 如有疑问，请联系仪器负责人! '));
            return false;
        }
    }

    public static function extra_form_validate($e, $object, $type, $form)
    {
        if ($object->name() == 'equipment' && $type == 'eq_sample') {
            $equipment = $object;
            if (!self::user_in_working_time(null, $equipment, $form['dtsubmit'])) {
                $form->set_error('dtsubmit', I18N::T('eq_sample_time', '送样时间不能在非工作时段!'));
            }

            if ($form['dtstart'] || $form['dtend']) {
                // 如果设定了测样时间, 直接测样问题
                $user_in_working_time =
                EQ_Sample_Time::user_in_working_time(null, $equipment, $form['dtstart'], $form['dtend']);
                if (!$user_in_working_time) {
                    $form->set_error('dtstart', I18N::T('eq_sample_time', '测样时间不能在非工作时段!'));
                }
            }
        }
    }

    public static function user_in_working_time($user, $equipment, $dtstart, $dtend = null)
    {
        if (!$user) {
            $user = L('ME');
        }

        if (
            $user->is_allowed_to('修改', $equipment)
            || Q("eq_sample_time[equipment={$equipment}]")->total_count() == 0
        ) {
            return true;
        }

        $times = mktime(date('H', $dtstart), date('i', $dtstart), date('s', $dtstart), 1, 1, 1971);
        $dates = strtotime(date('Y-m-d', $dtstart));
        if (isset($dtend)) {
            $eq_sample_times = Q("eq_sample_time[equipment={$equipment}][ltstart=$dtstart~$dtend|ltend=$dtstart~$dtend|ltstart~ltend=$dtstart|ltstart~ltend=$dtend]");
            $timee = mktime(date('H', $dtend), date('i', $dtend), date('s', $dtend), 1, 1, 1971);
            $datee = strtotime(date('Y-m-d', $dtend));
        } else {
            $eq_sample_times = Q("eq_sample_time[equipment={$equipment}][ltstart<=$dtstart][ltend>=$dtstart]");
        }

        if ($eq_sample_times->count() == 0) {
            return true;
        }

        // 转换预约时间，方便匹配规则
        foreach ($eq_sample_times as $eq_sample_time) {
            if (!$eq_sample_time->applies_to_user($user)) {
                continue;
            }

            if (isset($dtend)) {
                $diff = date('d', $datee) - date('d', $dates);
                if ($diff) {
                    $days = [
                        ['dates' => $dates, 'times' => $times, 'datee' => $dates, 'timee' => 31593599],
                        ['dates' => $datee, 'times' => 31507200, 'datee' => $datee, 'timee' => $timee],
                    ];

                    if ($diff > 1) {
                        for ($i = 1; $i < $diff; $i++) {
                            $days[] = [
                                'dates' => Date::next_time($dates, $i),
                                'times' => 31507200,
                                'datee' => Date::next_time($dates, $i),
                                'timee' => 31593599,
                            ];
                        }
                    }

                    foreach ($days as $day) {
                        if (
                            $eq_sample_time->check_time($day['dates'], $day['times'])
                            && $eq_sample_time->check_time($day['datee'], $day['timee'])
                        ) {
                            return true;
                        }

                    }
                } else {
                    if (
                        $eq_sample_time->check_time($dates, $times)
                        && $eq_sample_time->check_time($datee, $timee)
                    ) {
                        return true;
                    }

                }
            } else {
                if ($eq_sample_time->check_time($dates, $times)) {
                    return true;
                }
            }
        }

        Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample_time', '您无权在此时间进行送样，请选择合适的时间，如有疑问，请联系仪器负责人!'));
        return false;
    }

    public static function workingtime_sort($times, $key)
    {
        // 循环二维数组 进行开始时间排序
        // 添加非工作时间是先添加 今天的开始=>工作时间1 工作时间1=>工作时间2 ... 需要顺序
        usort($times, function (array $a, array $b) use ($key) {
            return $a[$key] <=> $b[$key];
        });
        return $times;
    }

    public static function get_components($e, $calendar, $dtstart, $dtend, $form)
    {
        $me = L('ME');
        $cdata = [];
        $times = [];
        if ($calendar->parent->name() != 'equipment' || $calendar->type != 'eq_sample') {
            $e->return_value = $cdata;
            return true;
        }

        $equipment = $calendar->parent;

        // 获取与当前时间有交集的工作时间
        $eq_sample_times = Q("eq_sample_time[equipment={$equipment}]");
        if ($form['start'] || $form['step']) {
            $eq_sample_times = $eq_sample_times->limit($form['start'], $form['step']);
        }

        // 不受限用户看不到非工作时间
        if ($me->is_allowed_to('修改', $equipment)) {
            $e->return_value = $cdata;
            return false;
        }

        foreach ($eq_sample_times as $eq_sample_time) {
            // 确认范围
            $begin = max($eq_sample_time->ltstart, $dtstart);
            $end = min($eq_sample_time->ltend, $dtend);

            $begin = $dtstart;
            $end = $dtend;

            $not_applied = !$eq_sample_time->applies_to_user($me);

            switch ($eq_sample_time->type) {
                case WT_RRule::RRULE_DAILY:
                    $date_begin = date_create(date('Y-m-d', $eq_sample_time->ltstart));
                    while ($begin <= $end) {
                        $diff = date_diff(
                            $date_begin,
                            date_create(date('Y-m-d', $begin))
                        );
                        $week = date('w', $begin);
                        // 确认间隔工作日与当前日子是否一致
                        if ($not_applied || $diff->d % $eq_sample_time->num != 0
                            || $begin < $eq_sample_time->ltstart
                            || $begin > $eq_sample_time->ltend
                        ) {
                            if (!$times[$week]) {
                                // off代表全天非工作时间
                                $times[$week] = [
                                    'type' => 'off',
                                    'start' => $begin,
                                    'end' => Date::next_time($begin) - 1,
                                ];
                            }
                        } else {
                            // 如果有工作时间 要先将之前的全天非工作时间删除
                            unset($times[$week]['type']);
                            unset($times[$week]['start']);
                            unset($times[$week]['end']);
                            $times[$week] = $eq_sample_time->clipping($times[$week], $begin);
                        }
                        $begin = Date::next_time($begin);
                    }
                    break;
                case WT_RRule::RRULE_WEEKDAY:
                case WT_RRule::RRULE_WEEKEND_DAY:
                case WT_RRule::RRULE_WEEKLY:
                    $days = explode(',', $eq_sample_time->days);
                    while ($begin <= $end) {
                        $week = date('w', $begin);
                        $diff = abs(date('W', $begin) - date('W', $eq_sample_time->ltstart));
                        if ($not_applied || !in_array($week, $days) || $diff % $eq_sample_time->num != 0
                            || $begin < $eq_sample_time->ltstart
                            || $begin > $eq_sample_time->ltend
                        ) {
                            if (!$times[$week]) {
                                $times[$week] = [
                                    'type' => 'off',
                                    'start' => $begin,
                                    'end' => Date::next_time($begin) - 1,
                                ];
                            }
                        } else {
                            unset($times[$week]['type']);
                            unset($times[$week]['start']);
                            unset($times[$week]['end']);
                            $times[$week] = $eq_sample_time->clipping($times[$week], $begin);
                        }
                        $begin = Date::next_time($begin);
                    }
                    break;
                case WT_RRule::RRULE_MONTHLY:
                    $days = explode(',', $eq_sample_time->days);
                    while ($begin <= $end) {
                        $week = date('w', $begin);
                        $diff = abs(date('m', $begin) - date('m', $eq_sample_time->ltstart));
                        if ($not_applied || !in_array(date('d', $begin), $days) || $diff % $eq_sample_time->num != 0
                            || $begin < $eq_sample_time->ltstart
                            || $begin > $eq_sample_time->ltend
                        ) {
                            if (!$times[$week]) {
                                $times[$week] = [
                                    'type' => 'off',
                                    'start' => $begin,
                                    'end' => Date::next_time($begin) - 1,
                                ];
                            }
                        } else {
                            unset($times[$week]['type']);
                            unset($times[$week]['start']);
                            unset($times[$week]['end']);
                            $times[$week] = $eq_sample_time->clipping($times[$week], $begin);
                        }
                        $begin = Date::next_time($begin);
                    }
                    break;
                case WT_RRule::RRULE_YEARLY:
                    $days = explode(',', $eq_sample_time->days);
                    while ($begin <= $end) {
                        $week = date('w', $begin);
                        $diff = abs(date('Y', $begin) - date('Y', $eq_sample_time->ltstart));
                        if ($not_applied || !in_array(date('m', $begin), $days) || $diff % $eq_sample_time->num != 0
                            || $begin < $eq_sample_time->ltstart
                            || $begin > $eq_sample_time->ltend
                        ) {
                            if (!$times[$week]) {
                                $times[$week] = [
                                    'type' => 'off',
                                    'start' => $begin,
                                    'end' => Date::next_time($begin) - 1,
                                ];
                            }
                        } else {
                            unset($times[$week]['type']);
                            unset($times[$week]['start']);
                            unset($times[$week]['end']);
                            $times[$week] = $eq_sample_time->clipping($times[$week], $begin);
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
                    'content' => (string) V('eq_empower:calendar/not_work_time', []),
                    // 'content' => I18N::T('eq_sample_time', '非工作时间'),
                ];
            } else {
                if ($week) {
                    $week = self::workingtime_sort($week, 'start');
                }

                $start = null;
                if ($week) {
                    foreach ($week as $time) {
                        $start = $start ?: Date::get_day_start($time['start']);

                        if ($start < $time['start']) {
                            $cdata[] = [
                                'id' => 0,
                                'dtStart' => $start,
                                'dtEnd' => $time['start'] - 1,
                                'color' => 9,
                                'calendar' => $calendar,
                                'content' => (string) V('eq_empower:calendar/not_work_time', []),
                            ];
                        }
                        $start = $time['end'];
                    }
                }

                if ($start && $start != Date::get_day_end($time['start'])) {
                    $cdata[] = [
                        'id' => 0,
                        'dtStart' => $start,
                        'dtEnd' => Date::get_day_end($time['start']) - 1,
                        'color' => 9,
                        'calendar' => $calendar,
                        'content' => (string) V('eq_empower:calendar/not_work_time', []),
                    ];
                }
            }
        }

        $e->return_value = $cdata;
        return false;
    }
}
