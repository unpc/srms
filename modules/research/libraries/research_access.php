<?php
class Research_Access
{
    public static function research_ACL($e, $user, $perm, $object, $options)
    {
        switch ($perm) {
            case '管理全部':
                if ($user->access('管理全部科研服务')) {
                    $e->return_value = true;
                    return false;
                }
            break;
            /*case '导出':
                if ($user->access('管理全部科研服务')
                    || $user->access('管理下属机构科研服务')
                    || Q("{$user}<contact research")->total_count()
                ) {
                    $e->return_value = true;
                    return false;
                }
                $e->return_value = false;
                return false;
            break;*/
            case '添加':
                if ($user->access('管理全部科研服务')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('管理下属机构科研服务')
                    && (is_string($object) && $object == 'research')
                ) {
                    $e->return_value = true;
                    return false;
                }
            break;
            case '修改':
            case '管理使用记录':
                if ($user->access('管理全部科研服务')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('管理下属机构科研服务')
                    && $object instanceof Research_Model
                    && $user->group->is_itself_or_ancestor_of($object->group)
                ) {
                    $e->return_value = true;
                    return false;
                }
                if (Q("{$user}<contact {$object}")->total_count()) {
                    $e->return_value = true;
                    return false;
                }
            break;
            case '修改联系人':
            case '修改组织机构':
                if ($user->access('管理全部科研服务')) {
                    $e->return_value = true;
                    return false;
                }
                if ($user->access('管理下属机构科研服务')
                    && $object instanceof Research_Model
                    && $user->group->is_itself_or_ancestor_of($object->group)
                ) {
                    $e->return_value = true;
                    return false;
                }
            break;
            case '删除':
                if ($user->is_allowed_to('修改', $object)
                    && !Q("{$object} research_record")->total_count()
                ) {
                    $e->return_value = true;
                    return false;
                }
            case '管理所有科研服务记录':
                if ($user->access('管理所有内容')
                    || $user->access('管理全部科研服务')
                ) {
                    $e->return_value = true;
                    return false;
                }
            break;
            case '添加使用记录':
            case '导出':
            case '导出使用记录':
                $e->return_value = true;
                return false;
                break;
        }
    }

    public static function research_record_ACL($e, $user, $perm, $object, $options)
    {
        switch ($perm) {
            case '编辑':
                if ($user->is_allowed_to('管理使用记录', $object->research)
                || $object->user->id == $user->id) {
                    $e->return_value = true;
                    return false;
                }
                break;
        }
    }
}