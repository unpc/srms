<?php
Class Servant_Access {

    static function is_accessible ($e, $name) {
        $me = L('ME');

        if ($me->is_allowed_to('管理', 'servant')) {
            $e->return_value = TRUE;
            return TRUE;
        }

        $e->return_value = FALSE;
        return FALSE;
    }

    static function servant_ACL ($e, $user, $perm, $object, $options) {
        switch ($perm) {
            case '管理':
                if ($user->access('管理下属机构建设') 
                || $user->access('管理所有内容')) {
                    $e->return_value = TRUE;
                    return TRUE;
                }

                $e->return_value = FALSE;
                return FALSE;
                break;
            default:
                $e->return_value = FALSE;
                return FALSE;
        }
    }

}