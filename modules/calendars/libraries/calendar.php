<?php
class Calendar
{
    public static function get_name($component)
    {
        if (Cal_Component_Model::TYPE_VFREEBUSY == $component->type) {
            $component_name = $component->name;
        } elseif (Cal_Component_Model::TYPE_VEVENT == $component->type) {
            if ('equipment' == $component->calendar->parent_name) {
                $component_name = $component->calendar->parent->name;
            } else {
                $component_name = $component->name;
            }
        }
        return $component_name;
    }
    
    public static function encrypt($data, $key)
    {
        $data = is_string($data) ? : json_encode($data);
        $content = Config::get('socket.key')["{$key}.pub"];
        $key = openssl_pkey_get_public($content);
        openssl_public_encrypt($data, $encrypted, $key, OPENSSL_PKCS1_OAEP_PADDING);
        return base64_encode($encrypted);
    }

    public static function decrypt($data, $key)
    {
        $content = Config::get('socket.key')["{$key}"];
        $key = openssl_pkey_get_private($content);
        openssl_private_decrypt($data, $output, $key, OPENSSL_PKCS1_OAEP_PADDING);
        return $output;
    }

    public static function _index_month($e, $tabs)
    {
        $form = Input::form();
        $form = self::refresh_history_form($form) ? :  $form;
        $dtstart = $form['st'] ?: time();
        $year = date('Y', $dtstart);
        $month = date('n', $dtstart);
        $monthend = date('t', $dtstart);
        $dtstart = mktime(0, 0, 0, $month, 1, $year);
        $dtend = mktime(23, 59, 59, $month, $monthend, $year);
        $dtprev = strtotime('-1 month', $dtstart);
        $dtnext = strtotime('+1 month', $dtstart);

        $view = V('calendars:calendar/month', [
            'dtstart' => $dtstart,
            'dtend' => $dtend,
            'dtprev' => $dtprev,
            'dtnext' => $dtnext,
        ]);
        $tabs->content = $view;
    }

    public static function _index_week($e, $tabs)
    {
        $calendar = $tabs->calendar;
        $form = Lab::form(function (&$old_form, &$form) {
        });
        $form_token = $form['form_token'] ? : Session::temp_token();
        $_SESSION[$form_token] = $form;

        if (!defined('WORKING_HOUR_START')) {
            define('WORKING_HOUR_START', 12);	//6:00AM
            define('WORKING_HOUR_STOP', 40);	// 8:00PM
        }

        $form = Input::form();
        $form = self::refresh_history_form($form) ? :  $form;
        $dtstart = $form['st'] ?: time();
        $date = getdate($dtstart);
        $fday = $date['mday']-$date['wday'];
        $dtstart = mktime(0, 0, 0, $date['mon'], $fday, $date['year']);
        $dtend = mktime(0, 0, 0, $date['mon'], $fday + 7, $date['year']);
        $dtprev = mktime(0, 0, 0, $date['mon'], $fday - 7, $date['year']);
        $dtnext = mktime(0, 0, 0, $date['mon'], $fday + 7, $date['year']);

        $form['dtstart'] = $dtstart;
        $form['dtend'] = $dtend;

        $view = V('calendars:calendar/week');
        $view->cal_week_rel = 'calweek_'.uniqid();
        $view->dtstart = $dtstart;
        $view->dtprev = $dtprev;
        $view->dtnext = $dtnext;
        $view->rules = Event::trigger('eq_empower.get_workingtime_week', $calendar, $dtstart);

        $now = time();
        $view->wday_now = ($now >= $dtstart && $now <= $dtnext) ? date('w', $now) : -1;
        switch ($calendar->type) {
            case 'eq_reserv':
                $equipment = $calendar->parent;
                if ($equipment->accept_block_time) {
                    $block_time = (array)$equipment->reserv_block_data;
                    $block_time['default']['interval_time'] = $equipment->reserv_interval_time;
                    $block_time['default']['align_time'] = $equipment->reserv_align_time;
                    $view->blocks = $block_time;
                }
                break;
            default:
                if (Input::form('block_time')) {
                    $view->blocks = Input::form('block_time');
                }
                break;
        }
        $tabs->content = $view;
    }

    public static function _index_day($e, $tabs)
    {
        $calendar = $tabs->calendar;
        $user = $tabs->user;
        $form = Lab::form(function (&$old_form, &$form) {});
        $form_token = $form['form_token'] ? : Session::temp_token();
        $_SESSION[$form_token] = $form;

        if (!defined('WORKING_HOUR_START')) {
            define('WORKING_HOUR_START', 12); // 6:00AM
            define('WORKING_HOUR_STOP', 40); // 8:00PM
        }

        $form = Input::form();
        $form = self::refresh_history_form($form) ? :  $form;
        $dtstart = $form['st'] ?: time();
        $date = getdate($dtstart);
        $fday = $date['mday'];
        $dtstart = mktime(0, 0, 0, $date['mon'], $fday, $date['year']);
        $dtend = mktime(0, 0, 0, $date['mon'], $fday + 1, $date['year']);
        $dtprev = mktime(0, 0, 0, $date['mon'], $fday - 1, $date['year']);
        $dtnext = mktime(0, 0, 0, $date['mon'], $fday + 1, $date['year']);

        $form['dtstart'] = $dtstart;
        $form['dtend'] = $dtend;

        $view = V('calendars:calendar/day');
        $view->cal_week_rel = 'calweek_' . uniqid();
        $view->dtstart = $dtstart;
        $view->dtprev = $dtprev;
        $view->dtnext = $dtnext;
        $view->user = $user;
        $view->rules = Event::trigger('eq_empower.get_workingtime_week', $calendar, $dtstart);

        $now = Date::time();
        $view->whour_now = ($now >= $dtstart && $now <= $dtnext) ? Date::format($now, 'H') : -1;
        switch ($calendar->type) {
            case 'eq_reserv':
                $equipment = $calendar->parent;
                if ($equipment->accept_block_time) {
                    $block_time = (array)$equipment->reserv_block_data;
                    $block_time['default']['interval_time'] = $equipment->reserv_interval_time;
                    $block_time['default']['align_time'] = $equipment->reserv_align_time;
                    $view->blocks = $block_time;
                }
                break;
            default:
                if (Input::form('block_time')) {
                    $view->blocks = Input::form('block_time');
                }
                break;
        }
        $tabs->content = $view;
    }


    /*****此处为日历 */
    public static function _index_calendar($e, $tabs)
    {
        $dtstart = Input::form('st') ?: time();
        $year = date('Y', $dtstart);
        $month = date('n', $dtstart);
        $monthend = date('t', $dtstart);
        $dtstart = mktime(0, 0, 0, $month, 1, $year);
        $dtend = mktime(23, 59, 59, $month, $monthend, $year);
        $dtprev = strtotime('-1 month', $dtstart);
        $dtnext = strtotime('+1 month', $dtstart);

        $view = V('calendars:calendar/month', [
            'dtstart' => $dtstart,
            'dtend' => $dtend,
            'dtprev' => $dtprev,
            'dtnext' => $dtnext,
        ]);
        $tabs->content = $view;
    }

    public static function _index_list($e, $tabs)
    {
        //翻页选择支持
        try{
            Event::trigger('registration_export.checkbox_change',$_REQUEST,'eq_reserv');
        }catch (Exception $e){}

        $calendar = $tabs->calendar;
        $form = Input::form();
        $form = Lab::form(function (&$old_form, &$form) {
        
        });
        $sort_asc = $form['sort_asc'];
        $sort_by = $form['sort'];


        $dtstart = $form['date'] ? : ($form['st'] ?: Date::time());
        $date = getdate($dtstart);
        $fday = $date['mday'];

		if ($form['dtstart']) {
			$dtstart = $form['dtstart'];
		} else {
			$dtstart = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);
		}

		if ($form['dtend']) {
			$dtend = $form['dtend'];
		} else {
			$dtend = mktime(0,0,0,$date['mon'], $fday + 7, $date['year']) - 1;
		}
        if ($dtstart) {
            $form['dtstart'] = $dtstart;
        }
        if ($dtend) {
            $form['dtend'] = $dtend;
        }

        $dtprev = mktime(0, 0, 0, $date['mon'], $fday - 7, $date['year']);
        $dtnext = mktime(0, 0, 0, $date['mon'], $fday + 7, $date['year']);
        $dtyes = mktime(0, 0, 0, $date['mon'], $fday - 1, $date['year']);
        $dttom = mktime(0, 0, 0, $date['mon'], $fday + 1, $date['year']);

        $stamp_of_now = getdate(time());
        $now = mktime(0, 0, 0, $stamp_of_now['mon'], $stamp_of_now['mday'], $stamp_of_now['year']);

        //hook here
        if ($calendar->id) {
            $components = Q("cal_component[calendar=$calendar][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D)");
        } else {
            $components = Q("cal_component:empty");
        }

        $form = new ArrayIterator($form);
        $new_components = Event::trigger('calendar.components.get', $calendar, $components, $dtstart, $dtend, 0, $form, 'list');
        $form = (array) $form;

        if ($new_components) {
            $components = $new_components;
        }

        $form_token = $form['form_token'] ? : Session::temp_token();
        $_SESSION[$form_token] = $form;

        $view = V('calendars:calendar/list', [
                'search_box' => $calendar->search_box,
                'form' => $form,
                'form_token' => $form_token,
                'sort_asc' => $sort_asc,
                'sort_by' => $sort_by
            ]);
        $view->form_token = $form_token;
        $view->dtstart = $dtstart;
        $view->dtend = $dtend;
        $view->dtprev = $dtprev;
        $view->dtnext = $dtnext;
        $view->dtyes = $dtyes;
        $view->dttom = $dttom;
        $view->now = $now;
        $view->components =  !$sort_by ? self::sort_components($components, $sort_asc) : $components;
        $tabs->content = $view;
    }

    
    public static function sort_components($components, $sort_asc)
    {
        if (!$components->total_count()) {
            return $components;
        }

        $components->reverse()->rewind();
        $component = $components->current();
        $components_origin_arr = [];
        $components_origin_arr[] = $component;

        while ($component = $components->next()) {
            $components_origin_arr[] = $component;
        }

        usort($components_origin_arr, function ($c1, $c2) {
            return $c2->dtstart - $c1->dtstart;
        });

        /*
            TODO 按照混合查询出来的component的名称类型进行排序，该处的计算方式可以正常出来，但是思路方式不是很明确。后期可能需要优化纠正
        */
        $component_arrs = [];
        $date = getdate($components_origin_arr[0]->dtstart);

        $component_arrs[$date['mday']][] = array_shift($components_origin_arr);
        while ($component = array_shift($components_origin_arr)) {
            $tmpdate = getdate($component->dtstart);
            if ($tmpdate['year'] == $date['year'] && $tmpdate['mday'] == $date['mday'] && $tmpdate['mon'] == $date['mon']) {
                $component_arrs[$date['yday']][] = $component;
            } else {
                $date = $tmpdate;
                $component_arrs[$date['yday']][] = $component;
            }
        }

        $component_arrs_sorted = [];

        if ($sort_asc) {
            foreach ($component_arrs as $component_arr) {
                usort($component_arr, function ($a, $b) {
                    return $a->dtstart - $b->dtstart;
                });
                $component_arrs_sorted = array_merge($component_arrs_sorted, $component_arr);
            }
        } else {
            foreach ($component_arrs as $component_arr) {
                usort($component_arr, function ($a, $b) {
                    return $b->dtstart - $a->dtstart;
                });
                $component_arrs_sorted = array_merge($component_arrs_sorted, $component_arr);
            }
        }
        return $component_arrs_sorted;
    }

    public static function refresh_history_form($form) {
		if ($_SESSION['calendar_index_form']) {
			$form['st'] = $_SESSION['calendar_index_form']['st'] ? : $form['st'];
			$form['sort_asc'] = $_SESSION['calendar_index_form']['sort_asc'] ? : $form['sort_asc'];
			$form['sort'] = $_SESSION['calendar_index_form']['sort'] ? : $form['sort'];
			$form['date'] = $_SESSION['calendar_index_form']['date'] ? : $form['date'];
		}
		return $form;
	}
}
