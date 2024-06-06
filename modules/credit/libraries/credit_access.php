<?php
class Credit_Access
{
    public static function User_ACL($e, $me, $perm_name, $object, $options)
    {
        if ($object->name() != 'user') {
            return;
        }
        switch ($perm_name) {
            case '查看信用记录':
                if ($me->id == $object->id || Q("{$me}<pi lab {$object}")->total_count()) {
                    $e->return_value = true;
                    return false;
                }
                break;
            default:
                break;
        }

        if ($me->access('管理所有成员信用分')
            || ($me->access('管理下属机构成员的信用分') && $me->group_id && $me->group->is_itself_or_ancestor_of($object->group))) {
            $e->return_value = true;
            return false;
        }
    }

    public static function Credit_ACL($e, $me, $perm_name, $object, $options)
    {
        switch ($perm_name) {
            case '查看列表':
                if ($me->access('管理所有成员信用分')
                    || $me->access('管理下属机构成员的信用分')
                    || Q("{$me}<incharge equipment")->total_count()) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看明细':
                if ($me->access('管理所有成员信用分')) {
                    $e->return_value = true;
                    return false;
                }

                /*if ($me->access('管理下属机构成员的信用分')
                    && $me->grouup->is_itself_or_ancestor_of($object->user->group)) {
                    $e->return_value = true;
                    return false;
                }*/
                break;
            case '解禁':
                if ($object->user->atime) {
                    $e->return_value = false;
                    return false;
                }

                $measures = O('credit_measures', ['ref_no' => 'unactive_user']);
                $res = Credit_Limit::check_condition($object->user, $measures);
                if ($me->access('管理所有成员信用分') && $res['status']) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '添加记录':
                if (Q("{$me}<incharge equipment")->total_count()) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '导出':
            case '打印':
            default:
                break;
        }
        if ($me->access('管理所有成员信用分')) {
            $e->return_value = true;
            return false;
        }
    }

    public static function Credit_Record_ACL($e, $me, $perm_name, $object, $options)
    {
        switch ($perm_name) {
            case '查看列表':
            case '统计数据':
            case '导出':
            case '打印':
            case '添加计分明细':
                if ($me->access('管理下属机构成员的信用分') || Q("{$me}<incharge equipment")->total_count()) {
                    $e->return_value = true;
                    return false;
                }
                break;
            default:
                break;
        }
        if ($me->access('管理所有内容') || $me->access('管理所有成员信用分')) {
            $e->return_value = true;
            return false;
        }
    }

    public static function Eq_Banned_ACL($e, $me, $perm_name, $object, $options)
    {
        switch ($perm_name) {
            case '查看列表':
                if ($me->is_allowed_to('查看仪器', 'eq_banned')
                    || $me->is_allowed_to('查看平台', 'eq_banned')
                    || $me->is_allowed_to('查看全局', 'eq_banned')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看仪器':
                if (Q("{$me}<incharge equipment")->total_count()
                    || $me->access('管理下属机构成员的信用分')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            case '查看平台':
                if ($me->access('管理下属机构成员的信用分') || $me->access('管理所有成员信用分') || $me->access('管理所有内容')) {
                    $e->return_value = true;
                    return false;
                }
                break;
            default:
                break;
        }
    }

    public static function is_accessible($e, $name)
    {
        $me = L('ME');
        if (!$me->access('管理所有内容')
            && !$me->access('管理组织机构')
            && !$me->access('管理所有成员信用分')
            && !$me->access('管理下属机构成员的信用分')
            && !($me->access('管理负责仪器的黑名单') && Q("{$me}<incharge equipment")->total_count())) {
            $e->return_value = false;
            return false;
        }
    }
}
