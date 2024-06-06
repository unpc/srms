<?php

class Dashboard_Access {

    static function dashboard_ACL($e, $me, $perm_name, $object, $options) {
        switch ($perm_name) {
            case '查看':
                if ($me->access('管理所有内容')
                    || $me->access('管理组织机构')
                    || $me->access('查看所属机构统计汇总信息')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            }
    }

    static function is_accessible ($e, $name) {
        $me = L('ME');
        if (!$me->is_allowed_to('查看', 'dashboard')) {
            $e->return_value = FALSE;
            return FALSE;
        }
    }
}
