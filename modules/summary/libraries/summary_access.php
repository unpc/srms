<?php
class Summary_Access
{

    public static function is_accessible($e, $name)
    {
        $me = L('ME');

//        if ($me->is_filler) {
//            $e->return_value = true;
//        }

        // Admin
        if ($me->access('管理所有内容')) {
            $e->return_value = true;
        }

        // 暂时兼容旧版权限
        if ($me->access('添加/修改下属机构的仪器') || $me->access('添加/修改所有机构的仪器')) {
            /* if (Module::is_installed('db_sync') && Db_Sync::is_master()) {
                $e->return_value = false;
            } else {
                $e->return_value = true;
            } */
            $e->return_value = true;
        }

        $summary_access = Config::get('perms.summary');
        unset($summary_access['-管理 (所有)']);
        unset($summary_access['-管理 (机构)']);
        unset($summary_access['-管理 (负责)']);
        unset($summary_access['-管理 (课题组)']);
        unset($summary_access['#name']);
        unset($summary_access['#icon']);
        $perms = $me->perms();
        $perms = array_keys($perms) ?: [];

        $is_equipment_charge = Config::get('perms.is_equipment_charge') ?: [];
        $is_lab_owner = Config::get('perms.is_lab_owner') ?: [];
        foreach ($summary_access as $access => $v) {
            if (in_array($access, $perms)) {
                // 需要有负责的容器
                if (in_array($access, $is_equipment_charge)){
                    if (Q("{$me}<incharge equipment")->total_count()) {
                        $e->return_value = true;
                    }
                }
                // 需要有课题组
                else if (in_array($access, $is_lab_owner)){
                    if (Q("lab[owner={$me}]")->total_count()) {
                        $e->return_value = true;
                    }
                }else {
                    $e->return_value = true;
                }
            }
        }

        return false;
    }

    public static function extra_roles ($e, $user, $user_roles) {
        $me = L('ME');
        if ($me->is_filler) {
            $role = O('role', ['weight' => ROLE_NRII_HELP]);
            if ($role->id) $user_roles[$role->id] = $role->id;
        }
        $e->return_value = $user_roles;
        return FALSE;
    }
}
