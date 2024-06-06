<?php

class Fund_Report {

    public static function is_accessible($e, $name) {
        $me = L('ME');

        $perms = Config::get("perms.fund_report");
        unset($perms['#name']);
        unset($perms['#icon']);

        $data = [];

        $is_equipment_charge = Config::get('perms.is_equipment_charge') ?: [];
        foreach ($perms as $key => $value) {
            if ($me->access($key)) {
                // 需要有负责的仪器
                if (in_array($key, $is_equipment_charge)){
                    if (Q("{$me}<incharge equipment")->total_count()) {
                        $e->return_value = true;
                    }
                } else {
                    $e->return_value = TRUE;
                }
                return FALSE;
            }
        }
// 取消次判断，增加"查看负责仪器的基金申报"权限
//        if (Q("{$me} equipment.incharge")->total_count()) {
//            $e->return_value = TRUE;
//            return FALSE;
//        }
            
        $e->return_value = FALSE;
        return FALSE;
    }

    public static function apply_ACL($e, $me, $perm_name, $object, $options){
        $e->return_value = false;

        list($username, $backend) = explode('|', $me->username);
        $is_genee = $username == 'genee' || $username == 'Support';

        switch ($perm_name) {
            case '审批基金申报':
                if ($me->is_allowed_to('列表基金申报', 'fund_report_apply') &&
                    ($me->access('初审基金申请单') || $me->access('复审基金申请单') || $is_genee)) {
                    $e->return_value = TRUE;
                }
                return false;
            case '列表基金申报':
                if ($me->access('查看下属机构的基金申报') || $me->access('查看负责仪器的基金申报') || $me->access('管理所有内容') || $is_genee) {
                    $e->return_value = TRUE;
                }
                return false;
            case '填报申请':
                if ($me->access('填报所有基金申请') || $is_genee || $me->access('填报负责仪器的基金申请')) {
                    $e->return_value = TRUE;
                }
                return;
        }
        return false;
    }

}