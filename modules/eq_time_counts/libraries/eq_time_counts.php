<?php

class EQ_Time_Counts
{

    static function time_counts_setting_breadcrumb($e, $equipment, $type)
    {
        if ($type != 'reserv') return;

        $e->return_value = [
            [
                'url' => $equipment->url(),
                'title' => H($equipment->name)
            ],
            [
                'url' => $equipment->url(NULL, NULL, NULL, 'edit'),
                'title' => I18N::T('eq_time_counts', '设置')
            ],
            [
                'url' => $equipment->url('reserv', NULL, NULL, 'empower_setting'),
                'title' => I18N::T('eq_time_counts', '预约次数&时长限制设置')
            ]
        ];
    }

    static function time_counts_setting_content($e, $equipment, $type)
    {
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
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_time_counts', '预约次数&时长限制更新失败!'));
                URI::redirect();
            }
            Q("eq_time_counts[equipment={$equipment}]")->delete_all();
            foreach ($form['startdate'] as $key => $value) {
                $fail = 0;
                $time = O('eq_time_counts', $form['id'][$key]);
                $time->equipment = $equipment;
                $rules = [];

                if ($form['per_reserv_time'][$key] < 0) {
                    $form->set_error('per_reserv_time[' . $key . ']', I18N::T('eq_reserv', '单次预约时长限制阈值不能小于0!'));
                    $fail++;
                }
                if ($form['total_reserv_counts'][$key] < 0 || floor($form['total_reserv_counts'][$key]) != $form['total_reserv_counts'][$key]) {
                    $form->set_error('total_reserv_counts[' . $key . ']', I18N::T('eq_reserv', '预约总次数限制阈值不能小于0且须为整数!'));
                    $fail++;
                }
                $time->per_reserv_time = $form['per_reserv_time'][$key];
                $time->total_reserv_counts = $form['total_reserv_counts'][$key];

                $time->controlall = $form['controlall'][$key];
                if ($time->controlall) {
                    $time->controluser = '';
                    $time->controllab = '';
                    $time->controlgroup = '';
                } else {
                    $time->controluser = ($form['select_user_mode_user'][$key] == 'on' && $form['user'][$key] != '{}')
                        ? $form['user'][$key] : '';

                    $time->controllab = ($form['select_user_mode_lab'][$key] == 'on' && $form['lab'][$key] != '{}')
                        ? $form['lab'][$key] : '';

                    $time->controlgroup = ($form['select_user_mode_group'][$key] == 'on' && $form['group'][$key] != '{}')
                        ? $form['group'][$key] : '';

                    if ($time->controluser == '' && $time->controllab == '' && $time->controlgroup == '') {
                        $form->set_error('controlall[' . $key . ']', I18N::T('eq_sample', '选择个别用户后请选择具体使用用户!'));
                        $fail++;
                    }
                }

                if ($form['startdate'][$key] > $form['enddate'][$key]) {
                    $form->set_error('working_date[' . $key . ']', I18N::T('eq_time_counts', '起始日期不能大于结束日期!'));
                    $fail++;
                }
                $time->ltstart = mktime(0, 0, 0, date('m', $form['startdate'][$key]), date('d', $form['startdate'][$key]), date('Y', $form['startdate'][$key]));
                $time->ltend = mktime(23, 59, 59, date('m', $form['enddate'][$key]), date('d', $form['enddate'][$key]), date('Y', $form['enddate'][$key]));
                $time->type = $form['repeat'][$key] ? $form['rtype'][$key] : 0;
                $time->num = $form['repeat'][$key] ? $form['rnum'][$key] : 1;

                switch ($time->type) {
                    case -2:    //用户选择工作日，默认为周一到周五
                        $rules = [1, 2, 3, 4, 5];
                        break;
                    case -3:    //用户选择周末，默认为周六周日
                        $rules = [0, 6];
                        break;
                    case 2:
                        $rules = array_keys($form['week_day'][$key] ?: []);
                        if (!$rules) {
                            $form->set_error('rule_form_' . $key, I18N::T('eq_time_counts', '请选择规则适用间隔的具体星期!'));
                            $fail++;
                        }
                        break;
                    case 3:
                        $rules = array_keys($form['month_day'][$key] ?: []);
                        if (!$rules) {
                            $form->set_error('rule_form_' . $key, I18N::T('eq_time_counts', '请选择规则适用间隔的具体日期!'));
                            $fail++;
                        }
                        break;
                    case 4:
                        $rules = array_keys($form['year_month'][$key] ?: []);
                        if (!$rules) {
                            $form->set_error('rule_form_' . $key, I18N::T('eq_time_counts', '请选择规则适用间隔的具体月份!'));
                            $fail++;
                        }
                        break;
                }
                $time->days = $rules;

                if (!$fail && $time->save()) {
                    $success++;
                    Log::add(strtr('[eq_sample] %user_name[%user_id] 修改%equipment_name[%equipment_id]预约时长&次数设置', [
                        '%user_name' => L('ME')->name,
                        '%user_id' => L('ME')->id,
                        '%equipment_name' => $equipment->name,
                        '%equipment_id' => $equipment->id,
                    ]), 'journal');
                } else $fails++;

                $ntime['id'] = $time->id;
                $ntime['equipment'] = $time->equipment->id;
                $ntime['startdate'] = $time->ltstart;
                $ntime['enddate'] = $time->ltend;
                $ntime['rtype'] = $time->type;
                $ntime['rnum'] = $time->num;
                $ntime['days'] = $time->days;
                $ntime['controlall'] = $time->controlall;
                $ntime['controluser'] = $time->controluser;
                $ntime['controllab'] = $time->controllab;
                $ntime['controlgroup'] = $time->controlgroup;
                $ntime['per_reserv_time'] = $time->per_reserv_time;
                $ntime['total_reserv_counts'] = $time->total_reserv_counts;
                $times[] = $ntime;
            }

            if ($success && !$fails) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_time_counts', '预约时长&次数设置更新成功!'));
                URI::redirect();
            } elseif ($success && $fails) {
                Lab::message(Lab::MESSAGE_NORMAL, I18N::T('eq_time_counts', '预约时长&次数设置部分更新成功!'));
            } elseif (!$form->no_error) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_time_counts', '预约时长&次数设置更新失败!'));
            }
        } else {
            $times = [];
            $sample_times = Q("eq_time_counts[equipment={$equipment}]:sort(id A)");

            foreach ($sample_times as $key => $value) {
                $time = [];
                $time['id'] = $value->id;
                $time['equipment'] = $value->equipment->id;
                $time['startdate'] = $value->ltstart;
                $time['enddate'] = $value->ltend;
                $time['rtype'] = $value->type;
                $time['rnum'] = $value->num;
                $time['days'] = explode(',', $value->days);
                $time['controlall'] = $value->controlall;
                $time['controluser'] = $value->controluser;
                $time['controllab'] = $value->controllab;
                $time['controlgroup'] = $value->controlgroup;
                $time['per_reserv_time'] = $value->per_reserv_time;
                $time['total_reserv_counts'] = $value->total_reserv_counts;
                $times[] = $time;
            }
        }
        if ($readonly) {
            $e->return_value = (string)V('eq_time_counts:time_counts_setting_readonly', ['times' => $times, 'form' => $form]);
        } else {
            $e->return_value = (string)V('eq_time_counts:time_counts_setting', ['times' => $times, 'form' => $form]);
        }
    }

    static function check_add_time_counts($e, $equipment, $component = null)
    {
        $dtstart = $component->dtstart;
        $dtend = $component->dtend;
        $user = L('ME');
        $e->return_value = self::check_time_counts($equipment, $dtstart, $dtend, $user, $component);
    }

    /**
     * 获取用户在指定时段中使用的规则
     * @param $user
     * @param $dtstart
     * @param $dtend
     * [
     *  'per_reserv_time' => [id=>value],
     *  'total_reserv_time' => [id=>value],
     *  'total_reserv_counts' => [id=>value],
     * ]
     */
    static function get_rules_by_eq_user_dt($equipment, $user, $dtstart, $dtend)
    {
        $me = L('ME');
        $times = [];
        //获取与当前时间有交集的工作时间
        $eq_reserv_times = Q("eq_time_counts[equipment={$equipment}][ltstart~ltend=$dtstart|ltstart~ltend=$dtend]:sort(id A)");

        foreach ($eq_reserv_times as $eq_reserv_time) {
            if (!$eq_reserv_time->check_user($me)) {
                continue;
            }
            //当前规则需要适用的范围
            $begin = strtotime(date('Y-m-d ', $dtstart));
            $end = strtotime(date('Y-m-d ', $dtend));
            switch ($eq_reserv_time->type) {
                case WT_RRule::RRULE_DAILY:
                    while ($begin <= $end && empty($times)) {
                        //因为可能存在跨天预约，需要分别匹配对应时段里的规则
                        $current_day_end = strtotime(date('Y-m-d ', $begin) . ' 23:59:59');
                        $diff = date_diff(date_create(date('Y-m-d', $eq_reserv_time->ltstart)), date_create(date('Y-m-d', $begin)));
                        if ($diff->d % $eq_reserv_time->num) {
                            $begin = Date::next_time($begin);
                            continue;
                        }
                        $times = [
                            'per_reserv_time' => $eq_reserv_time->per_reserv_time,
                            'total_reserv_time' => $eq_reserv_time->total_reserv_time,
                            'total_reserv_counts' => $eq_reserv_time->total_reserv_counts,
                            'begin_str' => date('Y-m-d H:i:s', $begin),
                            'end_str' => date('Y-m-d H:i:s', $current_day_end),
                            'begin' => $begin,
                            'end' => $current_day_end,
                            'id' => $eq_reserv_time->id,
                            'type' => $eq_reserv_time->type,
                            'days' => $eq_reserv_time->days,
                        ];
                        $begin = Date::next_time($begin);
                    }
                    break;
                case WT_RRule::RRULE_WEEKDAY:
                case WT_RRule::RRULE_WEEKEND_DAY:
                case WT_RRule::RRULE_WEEKLY:
                    $days = explode(',', $eq_reserv_time->days);
                    while ($begin <= $end && empty($times)) {
                        //因为可能存在跨天预约，需要分别匹配对应时段里的规则
                        $current_day_end = strtotime(date('Y-m-d ', $begin) . ' 23:59:59');
                        $week = date('w', $begin);
                        $diff = abs(date('W', $begin) - date('W', $eq_reserv_time->ltstart));
                        if (!in_array($week, $days)
                            || $diff % $eq_reserv_time->num) {
                            $begin = Date::next_time($begin);
                            continue;
                        }
                        $times = [
                            'per_reserv_time' => $eq_reserv_time->per_reserv_time,
                            'total_reserv_time' => $eq_reserv_time->total_reserv_time,
                            'total_reserv_counts' => $eq_reserv_time->total_reserv_counts,
                            'begin_str' => date('Y-m-d H:i:s', $begin),
                            'end_str' => date('Y-m-d H:i:s', $current_day_end),
                            'begin' => $begin,
                            'end' => $current_day_end,
                            'id' => $eq_reserv_time->id,
                            'type' => $eq_reserv_time->type,
                            'days' => $eq_reserv_time->days,
                        ];
                        $begin = Date::next_time($begin);
                    }
                    break;
                case WT_RRule::RRULE_MONTHLY:
                    $days = explode(',', $eq_reserv_time->days);
                    while ($begin <= $end && empty($times)) {
                        //因为可能存在跨天预约，需要分别匹配对应时段里的规则
                        $current_day_end = strtotime(date('Y-m-d ', $begin) . ' 23:59:59');
                        $day = date('d', $begin);
                        $diff = abs(date('m', $begin) - date('m', $eq_reserv_time->ltstart));

                        if (!in_array($day, $days)
                            || $diff % $eq_reserv_time->num != 0) {
                            $begin = Date::next_time($begin);
                            continue;
                        }
                        $times = [
                            'per_reserv_time' => $eq_reserv_time->per_reserv_time,
                            'total_reserv_time' => $eq_reserv_time->total_reserv_time,
                            'total_reserv_counts' => $eq_reserv_time->total_reserv_counts,
                            'begin_str' => date('Y-m-d H:i:s', $begin),
                            'end_str' => date('Y-m-d H:i:s', $current_day_end),
                            'begin' => $begin,
                            'end' => $current_day_end,
                            'id' => $eq_reserv_time->id,
                            'type' => $eq_reserv_time->type,
                            'days' => $eq_reserv_time->days,
                        ];
                        $begin = Date::next_time($begin);
                    }
                    break;
                case WT_RRule::RRULE_YEARLY:
                    $days = explode(',', $eq_reserv_time->days);
                    while ($begin <= $end && empty($times)) {
                        //因为可能存在跨天预约，需要分别匹配对应时段里的规则
                        $current_day_end = strtotime(date('Y-m-d ', $begin) . ' 23:59:59');
                        $month = date('m', $begin);
                        $diff = abs(date('Y', $begin) - date('Y', $eq_reserv_time->ltstart));
                        if (!in_array($month, $days)
                            || $diff % $eq_reserv_time->num) {
                            $begin = Date::next_time($begin);
                            continue;
                        }
                        $times = [
                            'per_reserv_time' => $eq_reserv_time->per_reserv_time,
                            'total_reserv_time' => $eq_reserv_time->total_reserv_time,
                            'total_reserv_counts' => $eq_reserv_time->total_reserv_counts,
                            'begin_str' => date('Y-m-d H:i:s', $begin),
                            'end_str' => date('Y-m-d H:i:s', $current_day_end),
                            'begin' => $begin,
                            'end' => $current_day_end,
                            'id' => $eq_reserv_time->id,
                            'type' => $eq_reserv_time->type,
                            'days' => $eq_reserv_time->days,
                        ];
                        $begin = Date::next_time($begin);
                    }
                    break;
                default:
                    //因为可能存在跨天预约，需要分别匹配对应时段里的规则
                    $rule_day_start = strtotime(date('Y-m-d ', $eq_reserv_time->ltstart) . ' 00:00:00');
                    $rule_day_end = strtotime(date('Y-m-d ', $eq_reserv_time->ltend) . ' 23:59:59');
                    $times = [
                        'per_reserv_time' => $eq_reserv_time->per_reserv_time,
                        'total_reserv_time' => $eq_reserv_time->total_reserv_time,
                        'total_reserv_counts' => $eq_reserv_time->total_reserv_counts,
                        'begin_str' => date('Y-m-d H:i:s', $rule_day_start),
                        'end_str' => date('Y-m-d H:i:s', $rule_day_end),
                        'begin' => $rule_day_start,
                        'end' => $rule_day_end,
                        'id' => $eq_reserv_time->id,
                        'type' => $eq_reserv_time->type,
                        'days' => $eq_reserv_time->days,
                    ];
                    break;
            }
            //产品要求最先匹配原则，因此time一唯数组
            if (!empty($times))
                break;
        }
        return $times;
    }

    /**
     * 获取需要判断预约次数和时间的起止区间列表
     * @param $equipment
     * @param $user
     * @param $rule
     * @param $dtstart
     * @param $dtend
     * @return array
     */
    static function get_near_days_for_reserv($equipment, $user, $rule, $dtstart, $dtend)
    {
        $start_end_zone = [];//验证预约次数的时间区间
        if ($rule['type'] == WT_RRule::RRULE_DAILY) {
            while ($dtstart < $dtend) {
                $day_start = strtotime(date('Y-m-d', $dtstart) . ' 00:00:00');
                $start_end_zone[$day_start] = [
                    'dtstart' => $day_start,
                    'dtend' => $day_start + 86400 - 1,
                    'dtstart_str' => date('Y-m-d H:i:s', $day_start),
                    'dtend_str' => date('Y-m-d H:i:s', $day_start + 86400 - 1),
                ];
                $dtstart = strtotime('+1 day', $day_start);
            }
        } elseif ($rule['type'] == WT_RRule::RRULE_WEEKLY) {
            $day_start = strtotime(date('Y-m-d', $dtstart) . ' 00:00:00');
            //取出对应连续时间段用来搜索预约
            $week_s = date('N', $day_start);
            $step_s = $week_s - 1;
            $step_e = 7 - $week_s;
            $week_s_tart = strtotime(" -{$step_s} day", $day_start);
            $week_s_end = strtotime(" +{$step_e} day", $day_start);
            $days = explode(',', $rule['days']);
            $index = array_search(0, $days);
            if ($index !== false) {
                $days[$index] = 7;
            }
            sort($days);
            for ($i = 0; $i < count($days); $i++) {
                $s = $week_s_tart + 86400 * ($days[$i] - 1);
                $e = $s + 86400 - 1;
                while (in_array($days[$i] + 1, $days)) {
                    $e = $e + 86400;
                    $i++;
                }
                $start_end_zone[$s] = [
                    'dtstart' => $s,
                    'dtend' => $e,
                    'dtstart_str' => date('Y-m-d H:i:s', $s),
                    'dtend_str' => date('Y-m-d H:i:s', $e),
                ];
            }
        } elseif ($rule['type'] == WT_RRule::RRULE_MONTHLY) {
            $day_start = strtotime(date('Y-m-d', $dtstart) . ' 00:00:00');
            //取出对应连续时间段用来搜索预约
            $month_s = date('j', $day_start);
            $step_s = $month_s - 1;
            $step_e = date('t', $dtstart) - $month_s;
            $month_s_tart = strtotime(" -{$step_s} day", $day_start);
            $month_s_end = strtotime(" +{$step_e} day", $day_start);
            $days = explode(',', $rule['days']);
            sort($days);
            for ($i = 0; $i < count($days); $i++) {
                $s = $month_s_tart + 86400 * ($days[$i] - 1);
                if ($s > $month_s_end)
                    continue;
                $e = $s + 86400 - 1;
                while (in_array($days[$i] + 1, $days)) {
                    $e = $e + 86400;
                    $i++;
                }
                $start_end_zone[$s] = [
                    'dtstart' => $s,
                    'dtend' => $e,
                    'dtstart_str' => date('Y-m-d H:i:s', $s),
                    'dtend_str' => date('Y-m-d H:i:s', $e),
                ];
            }
        } else {
            $rule_start = strtotime(date('Y-m-d', $rule['begin']) . ' 00:00:00');
            $rule_end = strtotime(date('Y-m-d', $rule['end']) . ' 23:59:59');
            $start_end_zone[$rule_start] = [
                'dtstart' => $rule_start,
                'dtend' => $rule_end,
                'dtstart_str' => date('Y-m-d H:i:s', $rule_start),
                'dtend_str' => date('Y-m-d H:i:s', $rule_end),
            ];
        }
        return $start_end_zone;
    }

    static function check_time_counts($equipment, $dtstart, $dtend, $user = null, $component)
    {
        $me = L('ME');
        if (!$user) $user = $me;

        if ($component->id && !defined('CLI_MODE')) {
            return [
                'allow' => true,
                'msg' => '',
            ];
        }

        $rules = self::get_rules_by_eq_user_dt($equipment, $user, $dtstart, $dtend);

        if (empty($rules))
            return [
                'allow' => true,
                'msg' => '',
            ];
        $length = (float)round(($dtend - $dtstart) / 3600, 4);
        if ($length > $rules['per_reserv_time'] && $rules['per_reserv_time'])
            return [
                'allow' => false,
                'msg' => I18N::T('eq_time_counts', '单次预约限制最长') . $rules['per_reserv_time'] . I18N::T('eq_time_counts', '小时'),
            ];

        //如果存在跨周期的极端情况，分别遍历
        $next_start = strtotime(date('Y-m-d', $dtstart));
        $current_end = $dtend;


        while ($next_start < $dtend) {
            if ($rules['type'] == WT_RRule::RRULE_DAILY && (date('d', $dtend) - date('d', $dtstart))) {
                $current_end = strtotime(date('Y-m-d', $next_start)) + 86400 - 1;
            }
            if ($rules['type'] == WT_RRule::RRULE_WEEKLY && (date('W', $dtend) - date('W', $dtstart))) {
                $week_s = date('N', $next_start);
                $step_s = $week_s - 1;
                $next_start = strtotime(" -{$step_s} day", $next_start);
                $current_end = $next_start + 86400 * 7 - 1;
            }
            if ($rules['type'] == WT_RRule::RRULE_MONTHLY && (date('n', $dtend) - date('n', $dtstart))) {
                $month_s = date('j', $next_start);
                $step_s = $month_s - 1;
                $next_start = strtotime(" -{$step_s} day", $next_start);
                $current_end = $next_start + 86400 * date('t', $next_start) - 1;
            }

            $start_end_zone = self::get_near_days_for_reserv($equipment, $user, $rules, $next_start, $current_end);
            $next_start = $current_end + 1;//下一个周期起始
            if (empty($start_end_zone))
                continue;

            $can_reserv = true;
            $where = [];
            foreach ($start_end_zone as $zone) {
                if ($rules['type'] == WT_RRule::RRULE_DAILY && $rules['total_reserv_counts']) {
                    $reservs = Q("eq_reserv[user={$user}][equipment={$equipment}][dtstart={$zone['dtstart']}~{$zone['dtend']}|dtend={$zone['dtstart']}~{$zone['dtend']}|dtstart~dtend={$zone['dtstart']}|dtstart~dtend={$zone['dtend']}]");
                    $has_counts = $component->id ? $reservs->total_count() - 1 : $reservs->total_count();
                    if ($has_counts >= $rules['total_reserv_counts'])
                        $can_reserv = false;
                }
                if (($rules['type'] == WT_RRule::RRULE_NONE || $rules['type'] == WT_RRule::RRULE_WEEKLY || $rules['type'] == WT_RRule::RRULE_MONTHLY) && $rules['total_reserv_counts']) {
                    $where[] = "((`e`.`dtstart`>='{$zone['dtstart']}' AND `e`.`dtstart`<='{$zone['dtend']}')
                 OR (`e`.`dtend`>='{$zone['dtstart']}' AND `e`.`dtend`<='{$zone['dtend']}')
                 OR (`e`.`dtstart` <= '{$zone['dtstart']}' AND `e`.`dtend` >= '{$zone['dtstart']}')
                 OR (`e`.`dtstart` <= '{$zone['dtend']}' AND `e`.`dtend` >= '{$zone['dtend']}'))
                 ";
                }
            }

            if (!empty($where)) {
                $where = implode(' OR ', $where);
                $sql = "
                    SELECT * FROM `eq_reserv` `e` WHERE equipment_id = {$equipment->id} AND user_id = {$user->id} AND ({$where})
                ";

                $db = Database::factory();
                $reservs = $db->query($sql)->rows() ?? [];
                $has_counts = $component->id ? count($reservs) - 1 : count($reservs);
                if ($has_counts >= $rules['total_reserv_counts'])
                    $can_reserv = false;
            }
            if (!$can_reserv)
                return [
                    'allow' => false,
                    'msg' => I18N::T('eq_time_counts', '预约限制每个周期最多预约') . $rules['total_reserv_counts'] . I18N::T('eq_time_counts', '次'),
                ];
        }
        return ['allow' => true, 'msg' => ''];
    }
}