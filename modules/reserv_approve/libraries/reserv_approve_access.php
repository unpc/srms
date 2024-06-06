<?php

class Reserv_Approve_Access {
    /*
    审核相关
    $object为reserv_approve对象
    */
    static function approve_ACL($e, $user, $perm, $object, $params) {
        $me = L('ME');
        $reserv = $object->reserv;

        switch ($perm) {
            case '查看全部':
                $e->return_value = TRUE;
                return FALSE;
                break;
            case '撤回':
                if ($user->id == $reserv->id){
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '审核':
                $is_incharge = Q("{$user} equipment.incharge")->total_count();
                $is_pi = Q("$user<pi lab")->total_count();
                if ($is_incharge || $is_pi) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '机主审核':
                if (Q("reserv_approve[reserv=$reserv]")->total_count() == 0
                    || Q("$reserv<approver $user")->total_count() == 0
                ) {
                    $e->return_value = FALSE;
                    return FALSE;
                }
                $equipment = $reserv->equipment;
                $incharges = Q("$equipment<incharge $user");
                if ($incharges->total_count() > 0) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case 'PI审核':
                if (Q("reserv_approve[reserv=$reserv]")->total_count() == 0
                    || Q("$reserv<approver $user")->total_count() == 0
                ) {
                    $e->return_value = FALSE;
                    return FALSE;
                }
                $lab = Q("$reserv->user lab")->current();
                $pis =  Q("$lab<owner $user");
                if ($pis->total_count() > 0) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            default:
                break;
        }
        $e->return_value = FALSE;
        return FALSE;
    }
}
