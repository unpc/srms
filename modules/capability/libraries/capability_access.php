<?php
class Capability_Access
{

    public static function is_accessible($e, $name)
    {
        $me = L('ME');


        if ($me->access('管理所有内容')) {
            $e->return_value = true;
        }

        $capability_access = Config::get('perms.capability');
        unset($capability_access['#name']);
        unset($capability_access['#icon']);
        $perms = $me->perms();
        $perms = array_keys($perms) ?: [];

        foreach ($capability_access as $access => $v) {
            if (in_array($access, $perms)) {
                $e->return_value = true;
            }
        }

        return false;
    }

    public static function equipment_ACL($e, $me, $perm_name, $object, $options){
        $e->return_value = false;

        list($username, $backend) = explode('|', $me->username);
        $is_genee = $username == 'genee';

        switch ($perm_name) {
            case '绩效审批':
                if (//$me->is_allowed_to('审批绩效申报', 'capability_equipment_task') &&
                    $me->is_allowed_to('列表绩效申报', 'capability_equipment_task')) {
                    $e->return_value = true;
                }
                return false;
            case '审批绩效申报':
                if ($is_genee
                    || $me->access('初审绩效')
                    || $me->access('复审绩效')
                ) {
                    $e->return_value = true;
                }
                return false;
            case '列表绩效申报':
                if ($is_genee
                    || $me->access('管理所有仪器绩效考核')
                    || $me->access('管理下属机构仪器绩效考核')
                ) {
                    $e->return_value = true;
                }
                return false;
            case '列表效益填报':
                if ($is_genee ||
                    $me->access('填报效益')) {
                    $e->return_value = true;
                }
                return false;
        }
        return false;
    }


}
