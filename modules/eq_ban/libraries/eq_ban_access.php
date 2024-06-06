<?php
class EQ_Ban_Access {

    static function banned_ACL($e, $me, $perm_name, $object, $options) {
        switch ($perm_name) {
            case '查看仪器':
                if ($me->access('管理所有成员信用分')
                    || $me->access('管理下属机构成员的信用分')
                    || $me->access('管理负责仪器的黑名单')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '添加仪器':
            case '编辑仪器':
                if ($me->access('管理所有成员信用分')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                if ($me->access('管理负责仪器的黑名单')) {
                    if (is_object($object)) {
                        if (Q("$me<@(incharge|contact) {$object->object}")->total_count()) {
                            $e->return_value = TRUE;
                        }
                        return FALSE;
                    }
                    else {
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                }
                break;
            case '查看机构':
            case '添加机构':
            case '编辑机构':
                if ($me->access('管理所有成员信用分')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                elseif ($me->access('管理下属机构成员的信用分')) {
                    if (is_object($object) && L('ME')->group->is_itself_or_ancestor_of($object->object)) {
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                    else {
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                }
                break;
            case '查看全局':
            case '添加全局':
            case '编辑全局':
                if ($me->access('管理所有成员信用分')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '查看违规记录':
                if ($me->access('管理所有内容')
                    || $me->access('管理所有成员信用分')
                    || $me->access('管理下属机构成员的信用分')
                    || Q("$me<pi lab")->total_count()) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '查看下属机构的违规记录':
                if ($me->access('管理下属机构成员的信用分')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '编辑仪器违规记录':
                if ($me->access('管理负责仪器的黑名单')) {
                    if (is_object($object)) {
                        if (Q("$me<@(incharge|contact) equipment}")->total_count()) {
                            $e->return_value = TRUE;
                        }
                        return FALSE;
                    }
                    else {
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                }
                break;
        }
    }

    //传入对象$object为equipment
    static function equipment_banned_ACL($e, $me, $perm_name, $object, $options) {
        if ($object->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) return;

        if (Equipments::user_is_eq_incharge($me, $object)) {
            $e->return_value = TRUE;
            return FALSE;
        }

        if ($perm_name == '修改黑名单设置' && $me->access('添加/修改所有机构的仪器')) {
            $e->return_value = TRUE;
            return FALSE;
        }

        if ($me->access('管理所有成员信用分')) {
            $e->return_value = TRUE;
            return FALSE;
        }
        if ( $me->group->id && $me->access('管理下属机构成员的信用分') && $me->group->is_itself_or_ancestor_of($object->group)) {
            $e->return_value = TRUE;
            return FALSE;
        }
    }

    static function is_accessible ($e, $name) {
        $me = L('ME');
        if (!$me->is_allowed_to('查看全局', 'eq_banned')
            && !$me->is_allowed_to('查看机构', 'eq_banned')
            && !$me->is_allowed_to('查看仪器', 'eq_banned')) {
            $e->return_value = FALSE;
            return FALSE;
        }
    }
}
