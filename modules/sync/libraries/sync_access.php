<?php

class Sync_Access
{
    //传入参数$object为equipment
    public static function equipment_ACL($e, $me, $perm_name, $object, $options)
    {
        $topic = Config::get('sync.topics');
        $objectIsSync = !preg_match('/equipment/',join(',',$topic))  ? false : true;
        switch ($perm_name) {
            case '删除':
                if ($object->platform != LAB_ID && $objectIsSync) {
                    $e->return_value = false;
                    return false;
                }
                break;
        }
    }

    public static function user_ACL($e, $user, $perm, $object, $options)
    {
        $rules = Config::get('rules.sync_rules');
        switch ($perm) {
            case '删除':
                if (isset($rules['user']['allow_delete']) && $rules['user']['allow_delete'] === false) {
                    $e->return_value = false;
                    return FALSE;
                    break;
                }
        }
    }

    static function lab_ACL($e, $user, $perm, $object, $options)
    {
        $rules = Config::get('rules.sync_rules');
        switch ($perm) {
            case '删除':
                if (isset($rules['lab']['allow_delete']) && $rules['lab']['allow_delete'] === false) {
                    $e->return_value = false;
                    return FALSE;
                    break;
                }
        }
    }

}
