<?php

class Approval_Message_PI extends Approval_Message
{

    public static function approval_message_approve_pi_to_user($e, $approved)
    {
        $auditor = $approved->auditor;
        $multi_lab = $GLOBALS['preload']['people.multi_lab'];
        if ($multi_lab) { //多课题组根据project发送给pi
            $pi = Q("{$approved->source->source->project->lab}<pi user")->current();
        } else {
            $pi = Q("{$approved->source->user} lab<pi user")->current();
        }

        $source = $approved->source->source;
        $charge = O('eq_charge', ['source' => $source]);

        if($source->name() == 'eq_reserv'){
            Notification::send('approval.need_approve_pi_eq_reserv', $pi, [
                '%pi' => Markup::encode_Q($pi),
                '%user' => Markup::encode_Q($source->user),
                '%time' => Date::format($source->ctime, 'Y/m/d H:i:s'),
                '%equipment' => Markup::encode_Q($source->equipment),
                '%dtstart' => Date::format($source->dtstart, 'Y/m/d H:i:s'),
                '%dtend' => Date::format($source->dtend, 'Y/m/d H:i:s'),
                '%money' => ( $charge && $charge->auto_amount ) ?  $charge->auto_amount : '0',
            ]);
        }elseif($source->name() == 'eq_sample'){
            Notification::send('approval.need_approve_pi_eq_sample', $pi, [
                '%pi' => Markup::encode_Q($pi),
                '%user' => Markup::encode_Q($source->sender),
                '%time' => Date::format($source->ctime, 'Y/m/d H:i:s'),
                '%equipment' => Markup::encode_Q($source->equipment),
                '%dtsubmit' => Date::format($source->dtsubmit, 'Y/m/d H:i:s'),
            ]);
        }

        return false;
    }

    public static function approval_message_approve_incharge_to_user($e, $approved)
    {
        $next_auditor = Q("{$approved->source->equipment}<incharge user");
        $all_next = [];
        $source = $approved->source->source;
        $charge = O('eq_charge', ['source' => $source]);
        foreach ($next_auditor as $next) {
            if ($source->name() == 'eq_sample' || $source->name() == 'eq_reserv') {
                Notification::send('approval.need_approve_incharge_eq_sample', $next, [
                    '%incharge' => Markup::encode_Q($next),
                    '%user' => $source->name() == 'eq_sample' ? Markup::encode_Q($source->sender) : Markup::encode_Q($source->user),
                    '%time' => Date::format($source->ctime, 'Y/m/d H:i:s'),
                    '%equipment' => Markup::encode_Q($source->equipment),
                    '%dtstart' => Date::format($source->dtstart, 'Y/m/d H:i:s'),
                    '%dtend' => Date::format($source->dtend, 'Y/m/d H:i:s'),
                    '%money' => ( $charge && $charge->auto_amount ) ?  $charge->auto_amount : '0',
                ]);
                $all_next[] = Markup::encode_Q($next);
            }
        }
    }

    public static function get_approval_type_str($approved)
    {
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
