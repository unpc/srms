<?php

class DB_Sync_Equipment
{
    public static function extra_follows($e, $follows = null, $user = null)
    {
        if (Db_Sync::is_slave() && DB_SYNC::is_module_unify_manage('follow')) {
            $user     = $user ? $user : L('ME');
            $selector = "equipment[site=" . LAB_ID . "]<object follow[user={$user}][object_name=equipment]:sort(ctime A)";
            if (Q($selector)->total_count()) {
                $e->return_value = Q($selector);
            } else {
                $e->return_value = Q('follow[id=0]');
            }
            return false;
        }
    }

    public static function equipment_post_submit($e, $form, $equipment)
    {
        if (DB_SYNC::is_module_unify_manage('equipment')) {
            $equipment->site = $form['site'];
        }
    }
}
