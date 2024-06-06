<?php

class Approval_Message {

    //过期发送消息
    public static function expired_message_reject_to_user($e, $approved) {
        Notification::send('approval_expired.to_user.sender', $approved->user, [
            '%type' => self::get_approval_type_str($approved),
            '%time'=> Date::format($approved->source->ctime, 'Y/m/d H:i:s'),
            '%eq_name'=> Markup::encode_Q($approved->source->equipment),
            '%approval_url' => $approved->source->user->url('mine_approval.'.$approved->flag),
        ]);
        return;
    }

    public static function approval_message_reject_to_user($e, $approved) {
        $auditor = $approved->auditor;
        $url = $approved->source->user->url('mine_approval.'.$approved->flag);
        Notification::send('approval.to_user.sender', $approved->source->user, [
                '%type' => self::get_approval_type_str($approved),
                '%user' => Markup::encode_Q($approved->source->user),
                '%time'=> Date::format($approved->source->ctime, 'Y/m/d H:i:s'),
                '%eq_name'=> Markup::encode_Q($approved->source->equipment),
                '%auditor'=> Markup::encode_Q($auditor),
                '%state'=> I18N::T('approval', '驳回'),
                '%extra'=> I18N::T('approval', '驳回原因：').($approved->description ?? '--'),
            ]);

        //产品和测试要求加上
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if ($multi_lab) { //多课题组根据project发送给pi
            $pi = Q("{$approved->source->source->project->lab}<pi user")->current();
        } else {
            $pi = Q("{$approved->source->user} lab<pi user")->current();
        }

        Notification::send('approval.to_user.sender', $pi, [
            '%type' => self::get_approval_type_str($approved),
            '%user' => Markup::encode_Q($approved->source->user),
            '%time'=> Date::format($approved->source->ctime, 'Y/m/d H:i:s'),
            '%eq_name'=> Markup::encode_Q($approved->source->equipment),
            '%auditor'=> Markup::encode_Q($auditor),
            '%state'=> I18N::T('approval', '驳回'),
            '%extra'=> I18N::T('approval', '驳回原因：').($approved->description ?? '--'),
        ]);
        return TRUE;
    }

    public static function approval_message_approve_pi_to_user($e, $approved) {
        $auditor = $approved->auditor;
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if ($multi_lab) { //多课题组根据project发送给pi
            $pi = Q("{$approved->source->source->project->lab}<pi user")->current();
        } else {
            $pi = Q("{$approved->source->user} lab<pi user")->current();
        }
        Notification::send('approval.to_auditor.sender', $pi, [
            '%type' => self::get_approval_type_str($approved),
            '%user'=> Markup::encode_Q($approved->source->user),
            '%time'=> Date::format($approved->source->ctime, 'Y/m/d H:i:s'),
            '%eq_name'=> Markup::encode_Q($approved->source->equipment),
            '%detail'=>'',
            '%auditor'=> Markup::encode_Q($pi)
        ]);
        return TRUE;
    }

    public static function approval_message_approve_incharge_to_user($e, $approved) {
        $auditor = $approved->auditor;
        $next_auditor = Q("{$approved->source->equipment}<incharge user");
        $all_next = [];
        foreach ($next_auditor as $next) {
            Notification::send('approval.to_auditor.sender', $next, [
                '%type' => self::get_approval_type_str($approved),
                '%user'=> Markup::encode_Q($approved->source->user),
                '%time'=> Date::format($approved->source->ctime, 'Y/m/d H:i:s'),
                '%eq_name'=> Markup::encode_Q($approved->source->equipment),
                '%detail'=> '',
                '%auditor'=> Markup::encode_Q($next)
            ]);
            $all_next[] = Markup::encode_Q($next);
        }
        self::approval_to_user($approved, $all_next);
    }

    public static function approval_message_pass_to_user($e, $approved) {
        $auditor = $approved->auditor;
        Notification::send('approval.to_user.sender', $approved->source->user, [
                '%type' => self::get_approval_type_str($approved),
                '%user'=> Markup::encode_Q($approved->source->user),
                '%time'=> Date::format($approved->source->ctime, 'Y/m/d H:i:s'),
                '%eq_name'=> Markup::encode_Q($approved->source->equipment),
                '%auditor'=> Markup::encode_Q($auditor),
                '%state'=> I18N::T('approval', '通过'),
                '%extra'=> '',
            ]);
        //产品和测试要求加上
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if ($multi_lab) { //多课题组根据project发送给pi
            $pi = Q("{$approved->source->source->project->lab}<pi user")->current();
        } else {
            $pi = Q("{$approved->source->user} lab<pi user")->current();
        }
        Notification::send('approval.to_user.sender', $pi, [
            '%type' => self::get_approval_type_str($approved),
            '%user' => Markup::encode_Q($approved->source->user),
            '%time'=> Date::format($approved->source->ctime, 'Y/m/d H:i:s'),
            '%eq_name'=> Markup::encode_Q($approved->source->equipment),
            '%auditor'=> Markup::encode_Q($auditor),
            '%state'=> I18N::T('approval', '通过'),
            '%extra'=> '',
        ]);
        return TRUE;
    }

    public static function approval_to_user($approved, $next) {
        Notification::send('approval.to_auditor.sender', $approved->source->user, [
            '%type' => self::get_approval_type_str($approved),
            '%user'=> Markup::encode_Q($approved->source->user),
            '%time'=> Date::format($approved->source->ctime, 'Y/m/d H:i:s'),
            '%eq_name'=> Markup::encode_Q($approved->source->equipment),
            '%detail'=> '',
            '%auditor'=> join('|', $next)
        ]);
    }

    public static function get_approval_type_str($approved) {
        switch ($approved->source->source->name()) {
            case 'eq_sample':
                return I18N::T('approval', '送样');
            case 'eq_reserv':
                return I18N::T('approval', '预约');
            case 'ue_training':
                return I18N::T('approval', '培训/授权');
        }
    }
}
