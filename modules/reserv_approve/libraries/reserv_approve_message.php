<?php

class Reserv_Approve_Message {

    // 给审判者发送新的待处理审核提醒
    static function send_to_approver($approver, $reserv) {
        Notification::send('reserv_approve.to_approvers.sender', $approver, [
            '%user'=> Markup::encode_Q($reserv->user),
            '%time'=> Date::format($reserv->ctime, 'Y/m/d H:i:s'),
            '%eq_name'=> Markup::encode_Q($reserv->equipment),
            '%approve_url' => URI::url('!reserv_approve/index.approve')
        ]);
        return;
    }

    // 给申请者发送已处理审核提醒
    static function send_to_user($approver, $reserv, $reject, $approve) {
        switch ($approve->status) {
            case Reserv_Approve_Model::STATUS_PI_APPROVE:
                $url = URI::url('!reserv_approve/index.mine.approve');
                break;
            case Reserv_Approve_Model::STATUS_INCHARG_APPROVE:
                $url = URI::url('!reserv_approve/index.mine.pass');
                break;
            case Reserv_Approve_Model::STATUS_DELETE:
                $url = URI::url('!reserv_approve/index.mine.reject');
                break;
        }
        if ($reserv->user->id != $approver->id) {
            Notification::send('reserv_approve.to_user.sender', $reserv->user, [
                '%time'=> Date::format($reserv->ctime, 'Y/m/d H:i:s'),
                '%eq_name'=> Markup::encode_Q($reserv->equipment),
                '%approver'=> Markup::encode_Q($approver),
                '%state'=> $reject ?
                    I18N::T('reserv_approve', '驳回') :
                    I18N::T('reserv_approve', '通过'),
                '%approve_url' => $url
            ]);
        }
        return;
    }

    static function send_to_incharges($approver, $reserv) {
        $latest_approve = Q("$reserv<reserv reserv_approve:limit(1):sort(id D)")->current();
        if ($latest_approve->status == Reserv_Approve_Model::STATUS_INCHARG_APPROVE) {
            Notification::send('reserv_approve_success.to_incharge.sender', $approver, [
                '%incharge' => Markup::encode_Q($latest_approve->user),
                '%time'=> date('Y-m-d H:i:s', $latest_approve->ctime),
                '%eq_name'=> Markup::encode_Q($reserv->equipment),
                '%dur'=> Date::range($reserv->dtstart, $reserv->dtend),
                '%reserv_url' => $reserv->equipment->url('reserv'),
            ]);
        }

        return;
    }

    static function send_to_user_overdue($reserv) {
        Notification::send('reserv_approve_overdue.to_user.sender', $reserv->user, [
            '%time'=> Date::format($reserv->ctime, 'Y/m/d H:i:s'),
            '%eq_name'=> Markup::encode_Q($reserv->equipment),
            '%approve_url' => URI::url('!reserv_approve/index.mine.cancel'),
        ]);
        return;
    }

    static function send_to_user_approve_change($reserv) {
        Notification::send('reserv_approve_change.to_user.sender', $reserv->user, [
            '%time'=> Date::format($reserv->ctime, 'Y/m/d H:i:s'),
            '%eq_name'=> Markup::encode_Q($reserv->equipment),
            '%approve_url' => URI::url('!reserv_approve/index.mine.approve'),
        ]);
        return;
    }
}
