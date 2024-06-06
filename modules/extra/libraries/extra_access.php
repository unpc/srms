<?php
/*
自定义表单权限设置
*/
class Extra_Access {

    static function extra_ACL($e, $user, $perm, $extra, $params) {
        if (!$extra->id) return;

        if ($extra->object_name == 'equipment') {
            $equipment = $extra->object;
            switch ($perm) {
                case '修改':
                    if ($extra->type == 'eq_reserv' && !$user->is_allowed_to('修改预约设置', $equipment) ||
                        $extra->type == 'eq_sample' && !$user->is_allowed_to('修改送样设置', $equipment) 
                    ) {
                        $e->return_value = FALSE;
                        return FALSE;
                    }
                    break;
                default:
                    break;
            }
        }
        $e->return_value = TRUE;
        return TRUE;
    }
}