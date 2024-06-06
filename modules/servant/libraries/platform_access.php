<?php
Class Platform_Access {

    static function Platform_ACL ($e, $user, $perm, $object, $options) {
        switch ($perm) {
            case '查看':
            case '修改':
            case '添加':
            case '删除':
                if ($user->access('管理下属机构建设') 
                || $user->access('管理所有内容')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                $e->return_value = FALSE;
                return FALSE;
            default:
                $e->return_value = FALSE;
                return FALSE;
        }

        return TRUE;
    }

    static function Object_ACL ($e, $user, $perm, $object, $options) {
        switch ($perm) {
            case '查看':
            case '修改':
            case '添加':
            case '删除':
                $code = LAB_ID;
                $platform = O('platform', ['code' => $code]);
                if ($platform->id) {
                    if ($object->id ) {
                        switch ($object->name()) {
                            case 'user':
                                if ($user->id == $object->id) {
                                    return TRUE;
                                }
                                break;
                            case 'lab':
                                if ($user->lab->id == $object->id) {
                                    return TRUE;
                                }
                                break;
                        }
                        if (!Q("$platform $object")->total_count()) {
                            $e->return_value = FALSE;
                            return FALSE;
                        }
                    }
                }
                break;
            default:
                $e->return_value = TRUE;
                return TRUE;
        }

        return TRUE;
    }

}