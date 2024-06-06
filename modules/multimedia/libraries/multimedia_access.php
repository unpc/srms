<?php
class MultiMedia_Access {
    static function operate_multimedia_is_allowed($e, $user, $perm, $object, $options) {
        switch ($perm) {
        case '管理':
            if ($user->access('多媒体前台管理')) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
        default:
            return FALSE;
        }
    }

    static function is_accessible($e, $name) {
        if (!L('ME')->is_allowed_to('管理', $name)) {
            $e->return_value = false;
            return false;
        }
    }
}
