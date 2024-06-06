<?php

class Approval_Access {

    //$params第一个参数为审批流程中的状态 参数1 当前的状态
    static function can_approval($e, $user, $params) {
        $e->return_value = Event::trigger("approval.{$params[0]}.access", $user, $params[1] ?? '');
        return FALSE;
    }
    
    //PI可以通过
    static function approve_pi_access($e, $user, $approval) {
        $e->return_value = FALSE;
        if ($approval == '') (bool)Q("lab[owner={$user}]")->total_count() ? $e->return_value = TRUE : '';
        else (bool)Q("$approval->user lab<pi $user")->total_count() ? $e->return_value = TRUE : '';
        return FALSE;
    }

    //Incharge可以通过
    static function approve_incharge_access($e, $user, $approval) {
        $e->return_value = FALSE;
        if ($approval == '') (bool)Q("{$user}<incharge equipment")->total_count() ? $e->return_value = TRUE : '';
        else (bool)Q("$approval->equipment<incharge $user")->total_count() ? $e->return_value = TRUE : '';
        return FALSE;
    }

    static function approve_done_access($e, $user, $param) {
        $e->return_value = TRUE;
        if ($param) $e->return_value = FALSE; //存在approval实体后，说明不再具体操作权限
        return FALSE;
    }
    
    static function approve_rejected_access($e, $user, $param) {
        $e->return_value = TRUE;
        if ($param) $e->return_value = FALSE;
        return FALSE;
    }

    static function approve_expired_access($e, $user, $param) {
        $e->return_value = TRUE;
        if ($param) $e->return_value = FALSE;
        return FALSE;
    }

    
    //查看最外层tab的权限
    static function approve_first_tab_access($e, $user, $approvel) {
        $flows = Config::get('flow.eq_reserv');
        foreach ($flows as $key => $val) { //把key遍历一遍。如果任一个权限则可以看见
            if (isset($val['action']) && Event::trigger("approval.{$key}.access", $user, '')){
                $e->return_value = TRUE;
                return FALSE;
                break;
            }
        }
        $e->return_value = FALSE;
        return FALSE;   
    }
}
