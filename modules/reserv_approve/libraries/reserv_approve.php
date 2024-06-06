<?php
class Reserv_Approve {

    static function modify_is_allowed($e, $user, $perm_name, $component, $options) {
        try {
            $parent = $component->calendar->parent;
            if ($parent->name() == 'equipment') {
                $reserv = O('eq_reserv', ['component'=>$component]);
                $approve = Q("$reserv<reserv reserv_approve:limit(1):sort(id D)")->current();
                $equipment = $reserv->equipment;

                if ($reserv->user->id == $user->id && Q("$user<pi lab")->total_count() && $approve->id && $approve->status == Reserv_Approve_Model::STATUS_PI_APPROVE) {
                    $e->return_value = TRUE;
                    return TRUE;
                }
                // 只有在机主审核通过之前的状态、或者机主工作时间驳回可修改预约
                if ((Q("{$equipment} user.incharge[id=$user->id]")->total_count()) && $approve->id && $approve->status == Reserv_Approve_Model::STATUS_INIT) {
                    $e->return_value = TRUE;
                    return TRUE;
                }
                if($approve->id && $approve->status != Reserv_Approve_Model::STATUS_INIT) {
                    throw new Exception(I18N::T('reserv_approve', '已经进入预约审核流程，不能修改！'));
                }
            }
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            $e->return_value = FALSE;
            return FALSE;
        }
    }

    static function delete_is_allowed($e, $user, $perm_name, $component, $options) {
        try {
            $parent = $component->calendar->parent;
            if ($parent->name() == 'equipment') {
                $reserv = O('eq_reserv', ['component'=>$component]);
                $approve = Q("$reserv<reserv reserv_approve:limit(1):sort(id D)")->current();
                // 只有在以下两种情况可删除
                // 1、机主审核通过之前的状态、或者机主工作时间驳回 
                // 2、审批负责人在过程中变化
                if($approve->id && $approve->status != Reserv_Approve_Model::STATUS_INIT
                    && !Reserv_Approve_Help::check_reapprove_by_approve($reserv, O('approve', $approve->id))
                    ) {
                    throw new Exception(I18N::T('reserv_approve', '已经进入预约审核流程，不能删除！'));
                }
            }
        } catch (Exception $e) {
            Lab::message(Lab::MESSAGE_ERROR, $e->getMessage());
            $e->return_value = FALSE;
            return FALSE;
        }
    }

    static function on_eq_reserv_saved($e, $reserv, $old_data, $new_data) {
        $me = L('ME');

        try {
            $approve = Q("reserv_approve[reserv=$reserv]:limit(1):sort(id D)")->current();

            $equipment = $reserv->equipment;
            $user = $reserv->user;
            if (!$approve->id && $reserv->approve_status == Szu_EQ_Reserv_Model::$state_num[Szu_EQ_Reserv_Model::STATE_NO_NEED] && !Q("{$equipment} user.incharge[id=$user->id]")->total_count()) {
                $approve_id = Reserv_Approve_Model::create($reserv);
                if (Q("$user<pi lab")->total_count()) {
                    O('reserv_approve', $approve_id)->operate($user, FALSE, '预约者为课题组PI自动审核通过');
                }
            }

            // 当预约发起者改变时，对应的待审核者也该变，而且重新判定是否需要提交审核
            elseif ($reserv->id && $new_data['user']->id != $old_data['user']->id) {
                if ($approve->id) {
                    $approve->change_user($reserv);
                }
                else {
                    if (Q("{$equipment} user.incharge[id=$user->id]")->total_count()) {
                        $reserv->approve_status = Szu_EQ_Reserv_Model::$state_num[Szu_EQ_Reserv_Model::STATE_NO_NEED];
                        $reserv->save();
                    } else {
                        $approve_id = Reserv_Approve_Model::create($reserv);
                        if (Q("$user<pi lab")->total_count()) {
                            O('reserv_approve', $approve_id)->operate($user, FALSE, '预约者为课题组PI自动审核通过');
                        }
                    }
                }
            } elseif (!$reserv->approve_status) {
                if ($reserv->user->id != $me->id) {
                    Reserv_Approve_Model::create($reserv);
                }
                else {
                    $reserv->approve_status = Szu_EQ_Reserv_Model::$state_num[Szu_EQ_Reserv_Model::STATE_NO_NEED];
                    $reserv->save();
                }
            }
        } catch (Exception $e) {

        }
    }

    // 审批保存时，记录下一个预约审批者
    static function on_reserv_approve_saved($e, $approve, $old_data, $new_data) {
        $reserv = $approve->reserv;
        foreach (Q("$reserv user.approver") as $old_approver) {
            $reserv->disconnect($old_approver, 'approver');
        }

        switch ($approve->status) {
            case Reserv_Approve_Model::STATUS_INIT:
                $approve_status = Szu_EQ_Reserv_Model::$state_num['approve'];
                $approver = Q("$reserv->user lab")->current()->owner;
                $reserv->connect($approver, 'approver');
                if ($reserv->user->id != $approver->id) {
                    Reserv_Approve_Message::send_to_approver($approver, $reserv);
                }
                $equipment = $reserv->equipment;
                foreach (Q("$equipment<incharge user") as $approver) {
                    $reserv->connect($approver, 'approver');
                }
                break;
            case Reserv_Approve_Model::STATUS_PI_APPROVE:
                $approve_status = Szu_EQ_Reserv_Model::$state_num['approve'];
                $equipment = $reserv->equipment;
                foreach (Q("$equipment<incharge user") as $approver) {
                    $reserv->connect($approver, 'approver');
                    Reserv_Approve_Message::send_to_approver($approver, $reserv);
                }
                break;
            case Reserv_Approve_Model::STATUS_INCHARG_APPROVE:
            case Reserv_Approve_Model::STATUS_PASS:
                $approve_status = Szu_EQ_Reserv_Model::$state_num['pass'];
                break;
            case Reserv_Approve_Model::STATUS_DELETE:
                if ($approve->user->id) {
                    $approve_status = Szu_EQ_Reserv_Model::$state_num['reject'];
                }
                else {
                    $approve_status = Szu_EQ_Reserv_Model::$state_num['cancel'];
                }
                break;
        }
        $reserv->approve_status = $approve_status;
        $reserv->save();
    }

    static function component_info_extra($e, $component) {
        $parent = $component->calendar->parent;
        if ($parent->name() == 'equipment') {
            $reserv = O('eq_reserv', ['component'=>$component]);
            $e->return_value = V('reserv_approve:info/component_extra', ['reserv'=>$reserv]);
        }
        return;
    }

    static function make_selector($pre_selector, $selector, $form, $ap = FALSE) {

        if ($ap) {
            if ($form['ap_dtstart_check'] == 'on') {
                $start_date = getdate($form['ap_dtstart']);
                $dtstart = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'], $start_date['year']);
            }

            if ($form['ap_dtend_check'] == 'on') {
                $end_date = getdate($form['ap_dtend']);
                $dtend = mktime(23, 59, 59, $end_date['mon'], $end_date['mday'], $end_date['year']);
            }
            if ($form['ap_organizer']) {
                unset($form['organizer']);
                $name = Q::quote($form['ap_organizer']);
                $pre_selector['organizer'] = "user[name*={$name}]";
            }
            if ($form['ap_equipment']) {
                unset($form['equipment']);
                $name = Q::quote($form['ap_equipment']);
                $pre_selector['ap_equipment'] = "equipment[name*={$name}]";
            }
        }
        else {
            if ($form['dtstart_check'] == 'on') {
                $start_date = getdate($form['dtstart']);
                $dtstart = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'], $start_date['year']);
            }
            if ($form['dtend_check'] == 'on') {
                $end_date = getdate($form['dtend']);
                $dtend = mktime(23, 59, 59, $end_date['mon'], $end_date['mday'], $end_date['year']);
            }
            if ($form['equipment']) {
                unset($form['ap_equipment']);
                $name = Q::quote($form['equipment']);
                $pre_selector['equipment'] = "equipment[name*={$name}]";
            }
            if ($form['ap_organizer']) {
                unset($form['organizer']);
                $name = Q::quote($form['ap_organizer']);
                $pre_selector['ap_organizer'] = "user[name*={$name}]";
            }
        }
        if ($dtstart || $dtend) {
            $selector = $selector . "[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]";
        }
        if (count($pre_selector)) {
            $selector = "( " . join(', ', $pre_selector) . " ) " . $selector;
        }

        return $selector;
    }

    static function get_date_value($form) {
        if ($form['dtstart_check'] && $form['dtend_check']) {
            $date_value = Date::format($form['dtstart'], 'Y/m/d') . ' - '. Date::format($form['dtend'], 'Y/m/d');
        }
        elseif ($form['dtstart_check']) {
            $date_value = Date::format($form['dtstart'], 'Y/m/d'). ' - '.I18N::T('eq_reserv', '最末');
        }
        elseif ($form['dtend_check']) {
            $date_value = I18N::T('eq_reserv', '最初'). ' - '. Date::format($form['dtend'], 'Y/m/d');
        }
        if ($form['ap_dtstart_check'] && $form['ap_dtend_check']) {
            $date_value = Date::format($form['ap_dtstart'], 'Y/m/d') . ' - '. Date::format($form['ap_dtend'], 'Y/m/d');
        }
        elseif ($form['ap_dtstart_check']) {
            $date_value = Date::format($form['ap_dtstart'], 'Y/m/d'). ' - '.I18N::T('ap_eq_reserv', '最末');
        }
        elseif ($form['ap_dtend_check']) {
            $date_value = I18N::T('ap_eq_reserv', '最初'). ' - '. Date::format($form['ap_dtend'], 'Y/m/d');
        }
        return $date_value;
    }

    // 额外的判断是否合并规则
    // 仅仅当不需审核、审核中且审核发起、审核中且被机主打回
    // 返回FALSE可合并
    static function merge_reserv($e, $source, $target) {
        $reserv = O('eq_reserv', ['component'=>$target]);
        $latest_approve = Q("$reserv<reserv reserv_approve:limit(1):sort(id D)")->current();
        $latest_status = $latest_approve->status;
        if (Q("{$reserv->user}<pi lab")->total_count() && $latest_status == Reserv_Approve_Model::STATUS_PI_APPROVE && $reserv->approve_status == Szu_EQ_Reserv_Model::$state_num[Szu_EQ_Reserv_Model::STATE_APPROVE]) {
                $e->return_value = FALSE;
                return TRUE;
        }
        if ($reserv->approve_status == Szu_EQ_Reserv_Model::$state_num[Szu_EQ_Reserv_Model::STATE_NO_NEED]
            || ($reserv->approve_status == Szu_EQ_Reserv_Model::$state_num[Szu_EQ_Reserv_Model::STATE_APPROVE]
                && $latest_status == Reserv_Approve_Model::STATUS_INIT)) {
            $e->return_value = FALSE;
            return FALSE;
        }
        else {
            $e->return_value = TRUE;
            return TRUE;
        }
    }

    static function cannot_access_equipment($e, $equipment, $params)
    {
        $me = $params[0];
        $dtstart = (int)$params[1];

        if ((int)$params[2]==0) {
            $dtend = $dtstart;
        }
        else {
            $dtend = (int)$params[2];
        }
        if ( $equipment->accept_reserv ) {
            $eq_reservs = Q("eq_reserv[equipment={$equipment}][user={$me}][dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}|dtstart~dtend={$dtstart}]");
            foreach ($eq_reservs as $eq_reserv){
                // 只有未审核的预约记录
                $approve = O('reserv_approve', [
                    'reserv' => $eq_reserv,
                    'status' => Reserv_Approve_Model::STATUS_PASS
                ]);
                if(!$approve->id && Q("reserv_approve[eq_reserv={$eq_reserv}]")->total_count()){
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '预约审核未通过，不可使用仪器'));
                    $e->return_value = TRUE;
                    return FALSE;
                }
            }
        }
    }
}

