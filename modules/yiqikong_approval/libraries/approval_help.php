<?php

class Approval_Help {

    public static function get_info_str($approved) {
        $user = $approved->source->user;
        $auditor = $approved->auditor;
        $time = $approved->ctime;
        switch ($approved->flag) {
            case 'approve':
                $status = I18N::T('reserv_approve', '用户%user提交审核', [
                        '%user' => V('approval:approval/user', ['user' => $user])
                    ]);
                break;
            case 'done':
                $status = I18N::T('reserv_approve', '机主%user审核通过', [
                        '%user' => V('approval:approval/user', ['user' => $auditor])
                    ]);
                break;
            case 'rejected':
                $status = I18N::T('approval', '机主%user驳回了审核', [
                        '%user' => V('approval:approval/user', ['user' => $auditor])
                    ]);
                break;
            case 'expired':
                $status = I18N::T('approval', '审核由于审核逾期被删除');
                break;
            default:
                break;
        }
        return sprintf('<span>%s</span>', $status);
    }

    public static function get_status_str($reserv) {
        $approval = O('approval', ['source' => $reserv]);
        switch ($approval->flag) {
            case 'approve':
                $equipment = $reserv->equipment;
                $incharges = Q("$equipment<incharge user");
                $status = I18N::T('approval', '仪器负责人未审核，待%next_user审核', [
                        '%next_user' => V('approval:approval/next_user', ['users' => $incharges])
                    ]);
                break;
            case 'done':
                $status = I18N::T('approval', '已通过');
                break;
            default:
                $status = I18N::T('approval', '无需审核');
                break;
        }
        return sprintf('<span>%s</span>', $status);
    }

    public static function make_selector($form, $flag, $object) {

        $me = L('ME');

        $selector = "approval[flag=$flag]";

        if (Module::is_installed('db_sync')) {
            $is_site_filter = true;
            if (DB_SYNC::is_slave()) {
                $site_filter = '[site=' . LAB_ID . ']';
            } elseif (DB_SYNC::is_master()) {
                if ($form['site']) {
                    $site_filter = "[site={$form['site']}]";
                }
            }
        }

        if ($object->name() == 'user') {
            if($form['equipment']){
                $eq_name = Q::quote($form['equipment']);
                $equipments = join(',', Q("equipment[name*=$eq_name]")->to_assoc('id', 'id'));
                $selector .= "[equipment=$equipments]";
            }

            if ($me->is_allowed_to('机主审核', 'approval')) {
                if ($is_site_filter) {
                    $pre_selector[] = "{$me}<incharge equipment{$site_filter}|{$me}";
                } else {
                    $pre_selector[] = "{$me}<incharge equipment|{$me}";
                }
            } else {
                $is_site_filter && $pre_selector[] = "equipment{$site_filter}";
                $selector .= "[user=$object]";
            }
        }

        if ($object->name() == 'equipment') {
            if (Equipments::user_is_eq_incharge($me, $object) && $me->is_allowed_to('机主预约审批', $object)) {
                $selector .= "[equipment=$object]";
            } else {
                $selector .= "[equipment=$object][user=$me]";
            }
        }

        if($form['dtstart_check']){
            $dtstart = Q::quote($form['dtstart']);
            $dtstart = Date::get_day_start($dtend);
			$selector .= "[dtstart>=$dtstart]";
		}
		if($form['dtend_check']){
			$dtend = Q::quote($form['dtend']);
            $dtend = Date::get_day_end($dtend);
			$selector .= "[dtend<=$dtend]";
		}
		if(!$form['dtstart_check'] && !$form['dtend_check']) {
            $form['dtend'] = Date::get_day_end(Date::time());
			$form['dtstart'] = Date::prev_time($form['dtend'], 1, 'm') + 1;
		}
        if($form['user']){
			$user = Q::quote(trim($form['user']));
            $users = join(',', Q("user[name*=$user]")->to_assoc('id', 'id'));
			$selector .= "[user_id=$users]";
		}

        if (count($pre_selector) > 0) {
            $pre_selector = '('.implode(', ', $pre_selector).') ';
        } else {
            $pre_selector = '';
        }
        return $selector = $pre_selector . $selector;
    }

    public static function links($approval) {
        $me = L('ME');
        $flow = Config::get('flow.eq_reserv');
        $is_incharge = Equipments::user_is_eq_incharge($me, $approval->equipment);
        $links = [];
        if ((($is_incharge && $me->is_allowed_to('机主审核', 'approval')) || $me->access('修改所有仪器的预约') || $me->access('修改下属机构仪器的预约')) && count($flow[$approval->flag]['action'])) {
            foreach ($flow[$approval->flag]['action'] as $name => $action) {
                $q_object = $name=='pass'? 'new_pass' : $name;
                $links[$name] = [
                    'text' => I18N::T('yiqikong_approval', $action['title']),
                    'tip' => I18N::T('yiqikong_approval', $action['title']),
                    'extra' => 'q-object='.$q_object.' q-event="click" q-src="' . H(URI::url('!yiqikong_approval/index')) .
                        '" q-static="' . H(['approval_id' => $approval->id]) .
                        '" class="blue"',
                ];
            }
        }
        $links['view'] = [
            'text' => I18N::T('approval', '查看'),
            'tip' => I18N::T('approval', '查看'),
            'extra' => 'q-object="view" q-event="click" q-src="' . H(URI::url('!yiqikong_approval/index')) .
                '" q-static="' . H(['approval_id' => $approval->id]) .
                '" class="blue"',
        ];
        if ($approval->user->id == $me->id && $approval->flag == 'approve') {
            $component = $approval->source->component;
            $links['edit'] = [
                'text' => I18N::T('yiqikong_approval', '修改'),
                'tip' => I18N::T('yiqikong_approval', '修改'),
                'extra' => 'q-object="edit_component" q-event="click" q-src="' . H(URI::url('!calendars/calendar')) .
                    '" q-static="' . H(['id' => $component->id, 'calendar_id' => $component->calendar->id, 'dtstart' => $component->dtstart, 'dtend' => $component->dtend, 'mode' => 'list', 'cal_week_rel' => TRUE]) .
                    '" class="blue"',
            ];
        }
        $links = new ArrayIterator($links);
		Event::trigger('approval.links', $approval, $links);
        return (array)$links;
    }

    public static function gpui_reserv_list_extra_info($e, $reserv, $info) {
        $approval = O('approval', ['source' => $reserv]);
        $info['approval'] = $approval->flag;
        return;
    }
}
