<?php

class Stock_Use {
    static function user_ACL($e, $user, $perm, $use, $options) {
        $stock = $use->stock;
        switch($perm) {
            case '修改' :
                $e->return_value = $use->user->id == $user->id || $user->is_allowed_to('修改', $stock) || $user->is_allowed_to('代人领用/归还', $stock);
                return FALSE;
                break;
            case '删除' :
                $e->return_value = $use->user->id == $user->id || $user->is_allowed_to('删除', $stock) || $user->is_allowed_to('代人领用/归还', $stock);
                return FALSE;
                break;
        }
    }
}
