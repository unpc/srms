<?php

class Approval_Access {

    static function approval_ACL($e, $user, $perm, $object, $params) {
        switch ($perm) {
            case '机主审核':
                if ($user->access('修改所有仪器的预约') || $user->access('修改下属机构仪器的预约')) {
                    $e->return_value = true;
                } else {
                    $incharge = Q("{$user} equipment.incharge");
                    if ($user->access('审批负责仪器的预约')) {
                        $e->return_value = $incharge->total_count() > 0 ? TRUE : FALSE;
                    }
                }
                return FALSE;
                break;
            default:
                break;
        }
        $e->return_value = FALSE;
        return FALSE;
    }

    public static function check_user($yiqikong_approval,$user) {
        if (!$yiqikong_approval->id) return false;

        $users = array_keys(json_decode($yiqikong_approval->uncontroluser, TRUE) ?? []);
        if (in_array($user->id, $users)) return true;

        $labs = implode(',', array_keys(json_decode($yiqikong_approval->uncontrollab, TRUE) ?? []));
        $user_labs = Q("$user lab[id={$labs}]")->total_count();
        if ($user_labs > 0) return true;

        $groups = implode(',', array_keys(json_decode($yiqikong_approval->uncontrolgroup, TRUE) ?? []));
        $user_groups = Q("$user tag_group[id={$groups}]")->total_count();
        if ($user_groups > 0) return true;

        return false;
    }

    static function cannot_access_equipment($e, $equipment, $params) {
        if (L('skip_cannot_access_hook')) {
            $e->return_value = FALSE;
            return TRUE;
        }
        $me = $params[0];
        $dtstart = (int)$params[1];

        if ((int)$params[2]==0) {
            $dtend = $dtstart;
            if ($equipment->advance_use_is_allowed) {
                $dtend += $equipment->advance_use_time;
            }
        }
        else {
            $dtend = (int)$params[2];
        }
        if ( $equipment->accept_reserv ) {
            $eq_reservs = Q("eq_reserv[equipment={$equipment}][user={$me}][dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}|dtstart~dtend={$dtstart}]");
            foreach ($eq_reservs as $eq_reserv){
                //只有未审核的预约记录
                if(Q("approval[source={$eq_reserv}]")->total_count()){
                    $approval = O('approval', ['source' => $eq_reserv,'flag'=>'done']);
                    if(!$approval->id){
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                }

            }
        }
    }
}
