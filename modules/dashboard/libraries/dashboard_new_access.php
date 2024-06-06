<?php

class Dashboard_New_Access
{
    public static function dashboard_ACL($e, $me, $perm_name, $object, $options)
    {
        switch ($perm_name) {
            case '查看':
                if ($me->access('查看数据总览')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            }
    }
    public static function is_accessible($e, $name)
    {
        $me = L('ME');
        if (!$me->is_allowed_to('查看', 'dashboard_new')) {
            $e->return_value = false;
            return false;
        }
    }
}
