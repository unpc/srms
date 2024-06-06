<?php

class EQ_Maintain_Access {
    static function equipment_maintain_ACL($e, $user, $perm, $equipment, $options) {
        $id = $equipment->id;
        switch ($perm) {
            case '查看维修记录':
                if ($user->access('管理所有内容') || $user->access('查看所有仪器的维修记录')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                elseif ($user->access('查看负责仪器的维修记录') 
                    && Q("{$user} equipment[id={$id}].incharge")->current()->id) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '修改维修记录':
                if ($user->access('管理所有内容') || $user->access('管理所有仪器的维修记录')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                elseif ($user->access('管理负责仪器的维修记录') 
                    && Q("{$user} equipment[id={$id}].incharge")->current()->id) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
            default:
                break;
        }
    }
}
