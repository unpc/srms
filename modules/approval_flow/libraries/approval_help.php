<?php

class Approval_Help
{
    public static function get_info_str($approved)
    {
        $flow = Config::get('flow.eq_reserv');
        $key_flow = array_keys($flow);
        if ($approved->flag == $key_flow[0]) { //第一步记录是用户审核 特殊处理
            $status = I18N::T('reserv_approve', '用户%user提交审核', [
                '%user' => V('approval_flow:approval/user', ['user' => $approved->source->user]),
            ]);
        } else {
            $status = Event::trigger("view_flow.{$approved->flag}.str", $approved);
        }
        return sprintf('<span>%s</span>', $status);
    }

    //日历显示的审核流程
    public static function get_status_str($reserv)
    {
        $approval = O('approval', ['source' => $reserv]);
        if (!$approval->id) {
            return sprintf('<span>%s</span>', I18N::T('approval', '无需审核'));
        }
        $status = Event::trigger("calendar_flow_status.{$approval->flag}.str", $reserv);
        return sprintf('<span>%s</span>', $status);
    }

    public static function make_selector($form, $flag)
    {
        $me = L('ME');

        $pre_selector = [];
        $flows = Config::get('flow.eq_reserv');
        if ($flows[$flag]['action']) {
            $pre_selector[] = Event::trigger("pre_selector_role.{$flag}") ?: '';
        }else {
            foreach ($flows as $key => $val) {
                if (!isset($val['action'])) continue;
                if (Event::trigger("approval.{$key}.access", $me, '')){
                    $pre_selector[] = Event::trigger("pre_selector_role.{$key}") ?: '';
                }
            }
        }

        $selector = Event::trigger("selector_role.{$flag}");

        if ($form['equipment']) {
            $equipment = Q::quote(trim($form['equipment']));
            $equipments = Q("equipment[name*=$equipment]")->to_assoc('id', 'id');
            $equipment_id = join(',', $equipments);
            $selector .= "[equipment_id=$equipment_id]";
        }
        if ($form['dtstart']) {
            $dtstart = Q::quote($form['dtstart']);
            $selector .= "[dtstart>=$dtstart]";
        }
        if ($form['dtend']) {
            $dtend = Q::quote($form['dtend']);
            $dtend = Date::get_day_end($dtend);
            $selector .= "[dtend<=$dtend]";
        }

        /* if (!$form['dtstart_check'] && !$form['dtend_check']) {
        $form['dtend'] = Date::get_day_end(Date::time());
        $form['dtstart'] = Date::prev_time($form['dtend'], 1, 'm') + 1;
        } */

        if ($form['user']) {
            $user = Q::quote(trim($form['user']));
            $selector .= "[user_id=$user]";
        }

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';

        switch ($sort_by) {
            case 'date':
            default:
                $selector .= ":sort(dtstart {$sort_flag})";
                break;
        }
        $selector = '(' . join(',', $pre_selector) . ') ' . $selector;
        return $selector;
    }

    public static function date_filter_handler()
    {
        return function (&$old_form, &$form) {
            if ($form['ctime_dtstart']) {
                $form['ctime_dtstart'] = Date::get_day_end($form['ctime_dtstart']);
            }

            if ($form['ctime_dtend']) {
                $form['ctime_dtend'] = Date::get_day_end($form['ctime_dtend']);
            }
        };
    }

    public static function make_tab_selector($form)
    {
        $me = L('ME');

        $pre_selector = $where = [];
        $selector = 'approval';

        if ($form['source_name']) {
            $where['source_name'] = "[source_name={$form['source_name']}]";
        }
        if ($form['equipment']) {
            if ($form['equipment'] instanceof Equipment_Model) {
                $pre_selector['equipment'] = "{$form['equipment']}";
            } else {
                $equipment = Q::quote(trim($form['equipment']));
                $pre_selector['equipment'] = "equipment[name*=$equipment]";
            }
        }
        if ($form['user']) {
            if ($form['user'] instanceof User_Model) {
                $pre_selector['user'] = "{$form['user']}";
            } else {
                $user = Q::quote($form['user']);
                $pre_selector['user'] = "user#$user";
            }
        }
        if ($form['lab']) {
            if ($form['lab'] instanceof Lab_Model) {
                $pre_selector['lab'] = "{$form['lab']} user";
            } else {
                $pre_selector['lab'] = "lab#{$form['lab']} user";
            }
        }
        if ($form['flag']) {
            $where['flag'] = "[flag={$form['flag']}]";
        }
        if ($form['ctime_dtstart']) {
            $ctime_dtstart = Q::quote($form['ctime_dtstart']);
            $ctime_dtstart = Date::get_day_start($ctime_dtstart);
            $where[] = "[ctime>=$ctime_dtstart]";
        }
        if ($form['ctime_dtend']) {
            $ctime_dtend = Q::quote($form['ctime_dtend']);
            $ctime_dtend = Date::get_day_end($ctime_dtend);
            $where[] = "[ctime<=$ctime_dtend]";
        }
        if ($form['source_dtstart']) {
            $source_dtstart = Q::quote($form['source_dtstart']);
            $source_dtstart = Date::get_day_start($source_dtstart);
            $where[] = "[dtstart>=$source_dtstart]";
        }
        if ($form['source_dtend']) {
            $source_dtend = Q::quote($form['source_dtend']);
            $source_dtend = Date::get_day_end($source_dtend);
            $where[] = "[dtstart<=$source_dtend]";
        }

        if (count($pre_selector)) {
            $selector = "(" . join(', ', (array) $pre_selector) . ") " . $selector;
        }
        if (count($where)) {
            $selector .= join("", $where);
        }

        $sort_by = $form['sort'];
        $sort_asc = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'D' : 'A';
        if ($sort_by) {
            switch ($sort_by) {
                default:
                    $selector .= ":sort({$sort_by} {$sort_flag})";
                    break;
            }
        }

        return $selector;
    }
    public static function links($approval) {
        $links = [];
        $links = new ArrayIterator($links);
		Event::trigger('approval.links', $approval, $links);
        return (array)$links;
    }
}
