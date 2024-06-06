<?php

class Gather_Perm
{

    public static function user_ACL($e, $user, $perm, $object, $options)
    {
        switch ($perm) {
            case '查看':
            case '修改':
                if ($object->source_id && $object->source_name) {
                    $e->return_value = false;
                    return false;
                }
                break;
        }
    }

}
