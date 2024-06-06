<?php
class Nrii_Access {
    static function operate_nrii_is_allowed($e, $user, $perm, $object, $options) {
        switch ($perm) {
        case '管理':
            if ($user->access('科技部对接管理')) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
        default:
            return FALSE;
        }
    }

    static function operate_nrii_equipment_is_allowed($e, $user, $perm, $object, $options) {
        switch ($perm) {
        case '编辑':
            if ($user->is_allowed_to('管理', 'nrii')) {
                $e->return_value = TRUE;
                return FALSE;
            }
            $equipment = O('equipment', $object->eq_id);
            if ($equipment->id && Q("{$equipment}<incharge {$user}")->total_count()) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
        case '上传至科技部':
            if($object->nrii_status != Nrii_Equipment_Model::NRII_STATUS_SUCCEED && $object->shen_status == Nrii_Equipment_Model::SHEN_STATUS_FINISH){
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
        case '审核':
            if($user->access('管理下属机构的省部科技厅对接') && $object->shen_status == Nrii_Equipment_Model::SHEN_STATUS_WAIT){
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
        default:
            return FALSE;
        }
    }

    static function is_accessible($e, $name) {
        $me = L('ME');
        if (
                (
                    ! $me->is_allowed_to('管理', $name)
                    &&
                    ! Q("{$me}<incharge equipment")->total_count()
                )
           ) {
            $e->return_value = $is_accessible;
            return FALSE;
        }
    }
}
